<?php

/**
 * Handle DAO events for the ActivityContact entity type.
 */
class CRM_Partneraccess_Listener_ActivityContact {

  private static $activityRecordTypes = array();
  private static $volGroupType = 'varl_partner_access_static_volunteer';
  private static $targetRecordTypes = array('Activity Assignees', 'Activity Targets');

  /**
   * @param CRM_Activity_DAO_ActivityContact $activityContact
   * @return array
   * @throws CiviCRM_API3_Exception
   *   Throws exception if not a volunteer activity or if actvity not found.
   */
  private static function fetchVolunteerActivity(CRM_Activity_DAO_ActivityContact $activityContact) {
    return civicrm_api3('Activity', 'getsingle', array(
      'activity_type_id' => 'Volunteer',
      'id' => $activityContact->id,
      'api.ActivityContact.get' => array(
        'record_type_id' => array('IN' => self::$targetRecordTypes),
      ),
    ));
  }

  /**
   * @param mixed $contactId
   * @param mixed $partnerId
   * @return boolean
   *   Returns true if contact is the volunteer for any activity for which the
   *   partner is the target/beneficiary, else false.
   */
  private static function hasVolunteerActivityWith($contactId, $partnerId) {
    return (boolean) civicrm_api3('Activity', 'getcount', array(
      'activity_type_id' => 'Volunteer',
      'assignee_contact_id' => $contactId,
      'target_contact_id' => $partnerId,
    ));
  }

  /**
   * @return array
   *   Activity record types, keyed by name.
   */
  private static function fetchActivityRecordTypes() {
    if (empty(self::$activityRecordTypes)) {
      $api = civicrm_api3('ActivityContact', 'getoptions', array(
        'field' => "record_type_id",
      ));
      self::$activityRecordTypes = array_flip($api['values']);
    }
    return self::$activityRecordTypes;
  }

  /**
   * Parses the chained Activity API call.
   *
   * In effect validates that both ends (i.e., partner and volunteer) of the
   * relevant Activity are present.
   *
   * @param array $activity
   *   @see CRM_Partneraccess_Listener_ActivityContact::fetchActivity().
   * @return array
   *   An array of arrays. Volunteer contact IDs keyed by partner contact ID.
   *   The array is empty if both sides of the activity are not present.
   */
  private static function keyVolunteersByPartner(array $activity) {
    $result = array();
    $recordTypeOptions = self::fetchActivityRecordTypes();

    $contactIds = array();
    foreach (self::$targetRecordTypes as $recordTypeString) {
      $contactIds[$recordTypeString] = array();
      $recordTypeNumeric = $recordTypeOptions[$recordTypeString];

      foreach ($activity['api.ActivityContact.get']['values'] as $activityContact) {
        if ($activityContact['record_type_id'] === $recordTypeNumeric) {
          $contactIds[$recordTypeString][] = $activityContact['contact_id'];
        }
      }

      // If empty after looping through all the activity contacts, it means we
      // don't have both contacts for the volunteer activity.
      if (empty($contactIds[$recordTypeString])) {
        return $result;
      }
    }

    foreach ($contactIds['Activity Targets'] as $partnerId) {
      $result[$partnerId] = $contactIds['Activity Assignees'];
    }
    return $result;
  }

  /**
   * Handler for ActivityContact DAO insert or update events.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   * @return void
   */
  public static function handleUpsert(\Symfony\Component\EventDispatcher\Event $event) {
    if (isset($event->object)) {
      try {
        $activity = self::fetchVolunteerActivity($event->object);
      }
      catch (Exception $ex) {
        // An exception means it wasn't an ActivityContact of type Volunteer; bail out.
        return;
      }

      foreach (self::keyVolunteersByPartner($activity) as $partnerId => $contactIds) {
        foreach ($contactIds as $contactId) {
          CRM_Partneraccess_GroupMembershipManager::add($contactId, self::$volGroupType, $partnerId);
        }
      }
    }
  }

  /**
   * Handler for ActivityContact DAO delete events.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   * @return void
   */
  public static function handleDelete(\Symfony\Component\EventDispatcher\Event $event) {
    if (isset($event->object)) {
      try {
        $activity = self::fetchVolunteerActivity($event->object);
      }
      catch (Exception $ex) {
        // An exception means it wasn't an ActivityContact of type Volunteer; bail out.
        return;
      }

      foreach (self::keyVolunteersByPartner($activity) as $partnerId => $contactIds) {
        foreach ($contactIds as $contactId) {
          if (!self::hasVolunteerActivityWith($contactId, $partnerId)) {
            CRM_Partneraccess_GroupMembershipManager::remove($contactId, self::$volGroupType, $partnerId);
          }
        }
      }
    }
  }

}
