<?php

/**
 * Handle DAO events for the ActivityContact entity type.
 */
class CRM_Partneraccess_Listener_Activity extends CRM_Partneraccess_Listener {

  private static $activityRecordTypes = array();
  protected static $volGroupType = 'varl_partner_access_static_volunteer';
  protected static $targetRecordTypes = array('Activity Assignees', 'Activity Targets');

  /**
   * @param mixed $contactId
   * @param mixed $partnerId
   * @param mixed $excludeActivityId
   *   Optional. If provided, the activity specified by ID will not count
   *   toward the volunteer's activity total with the specified partner. Useful
   *   when invoked in the context of a pre-delete; without the ability to
   *   exclude the about-to-be-deleted activity, the result would always be true.
   * @return boolean
   *   Returns true if contact is the volunteer for any activity for which the
   *   partner is the target/beneficiary, else false.
   */
  protected static function hasVolunteerActivityWith($contactId, $partnerId, $excludeActivityId = NULL) {
    $params = array(
      'activity_type_id' => 'Volunteer',
      'assignee_contact_id' => $contactId,
      'target_contact_id' => $partnerId,
    );
    if (!empty($excludeActivityId)) {
      $params['id'] = array('!=' => $excludeActivityId);
    }
    return (boolean) civicrm_api3('Activity', 'getcount', $params);
  }

  /**
   * @return array
   *   Activity record types, keyed by name.
   */
  protected static function fetchActivityRecordTypes() {
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
  protected static function keyVolunteersByPartner(array $activity) {
    $result = array();
    $recordTypeOptions = self::fetchActivityRecordTypes();

    $contactIds = array();
    foreach (self::$targetRecordTypes as $recordTypeString) {
      $contactIds[$recordTypeString] = array();
      $recordTypeNumeric = $recordTypeOptions[$recordTypeString];

      foreach ($activity['api.ActivityContact.get']['values'] as $activityContact) {
        if ($activityContact['record_type_id'] == $recordTypeNumeric) {
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

}
