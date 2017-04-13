<?php

/**
 * Manages membership of contacts in partner-specific groups used for access control.
 */
class CRM_Partneraccess_GroupMembershipManager {

  /**
   * Convenience wrapper around self::manage().
   */
  public static function add($contactId, $groupType, $partnerId) {
    self::manage('Added', $contactId, $groupType, $partnerId);
  }

  /**
   * Convenience wrapper around self::manage().
   */
  public static function remove($contactId, $groupType, $partnerId) {
    self::manage('Removed', $contactId, $groupType, $partnerId);
  }

  /**
   * Manages a contact's membership in a partner group.
   *
   * @param string $action
   *   See spec for api.GroupContact.create, field "status."
   * @param mixed $contactId
   *   Int or int-like string representing the contact whose group membership is
   *   being managed.
   * @param string $groupType
   *   See Group.mgd.php.
   * @param mixed $partnerId
   *   Int or int-like string representing the partner to which the contacts in
   *   the group are related.
   */
  private static function manage($action, $contactId, $groupType, $partnerId) {
    $group = self::getPartnerGroup(array(
      'group_type' => $groupType,
      'partner_id' => $partnerId,
    ));

    if (!empty($group['name'])) {
      civicrm_api3('GroupContact', 'create', array(
        'contact_id' => $contactId,
        'group_id' => $group['name'],
        'status' => $action,
      ));
    }
  }

  /**
   * Retrieves a partner group.
   *
   * TODO: Consider moving this to a class that is about groups rather than
   * group membership.
   *
   * @param array $params
   *   To allow fetching by other criteria in the future, we accept a keyed array.
   *   Presently the following keys are required:
   *     - group_type - See Group.mgd.php.
   *     - partner_id - The contact ID of the partner to which the contacts in
   *                    the group are related.
   * @return array
   *   Array containing the properties of the single matching partner group.
   *   Array is empty if a single matching group could not be found.
   */
  public static function getPartnerGroup($params) {
    $groupType = CRM_Utils_Array::value('group_type', $params);
    $partnerId = CRM_Utils_Array::value('partner_id', $params);
    if (!isset($groupType, $partnerId)) {
      CRM_Core_Error::fatal("Missing required parameter 'group_type' and/or 'partner_id.'");
    }

    $config = CRM_Partneraccess_Config::singleton();
    $customFieldName = $config->getPartnerCustomFieldApiName();
    $parentGroupId = $config->getParentGroupId();

    try {
      $result = civicrm_api3('Group', 'getsingle', array(
        $customFieldName => $partnerId,
        'group_type' => $groupType,
        'parents' => $parentGroupId,
      ));
    }
    catch (Exception $e) {
      $result = array();
    }

    return $result;
  }

  // TODO: refactor into another class with smaller functions
  // TODO: need a separate handler for deletes
  public static function processActivityEvent($event) {
    Civi::log()->debug(__METHOD__, array($event->object));

    $targetRecordTypes = array('Activity Assignees', 'Activity Targets');
    if (isset($event->object) && is_a($event->object, 'CRM_Activity_DAO_ActivityContact')) {
      try {
        $activity = civicrm_api3('Activity', 'getsingle', array(
          'activity_type_id' => 'Volunteer',
          'id' => $event->object->id,
          'api.ActivityContact.get' => array(
            'record_type_id' => array('IN' => $targetRecordTypes),
          ),
        ));
      }
      catch (Exception $e) {
        // bail out if the activity isn't a volunteer activity
        return;
      }

      $api = civicrm_api3('ActivityContact', 'getoptions', array(
        'field' => "record_type_id",
      ));
      $recordTypeOptions = array_flip($api['values']);

      $contactIds = array();
      foreach ($targetRecordTypes as $recordTypeString) {
        $contactIds[$recordTypeString] = array();
        $recordTypeNumeric = $recordTypeOptions[$recordTypeString];

        foreach ($activity['api.ActivityContact.get']['values'] as $activityContact) {
          if ($activityContact['record_type_id'] === $recordTypeNumeric) {
            $contactIds[$recordTypeString][] = $activityContact['contact_id'];
          }
        }

        if (empty($contactIds[$recordTypeString])) {
          return;
        }
      }

      // untested code below
      foreach ($contactIds['Activity Targets'] as $partnerId) {
        foreach ($contactIds['Activity Assignees'] as $contactId) {
          self::add($contactId, 'varl_partner_access_static_volunteer', $partnerId);
        }
      }
    }
  }

}
