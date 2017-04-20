<?php

/**
 * Handle DAO events for the ActivityContact entity type.
 */
class CRM_Partneraccess_Listener_ActivityContact extends CRM_Partneraccess_Listener {

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
      'id' => $activityContact->activity_id,
      'api.ActivityContact.get' => array(
        'record_type_id' => array('IN' => self::$targetRecordTypes),
      ),
    ));
  }

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
  private static function hasVolunteerActivityWith($contactId, $partnerId, $excludeActivityId = NULL) {
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
   * Handler for ActivityContact DAO pre-delete events.
   *
   * ActivityContact deletes cannot be ultimately handled here because of how
   * the Activity BAO works. In many (all?) cases, when an Activity is edited,
   * ALL the ActivityContacts are removed and then re-added if appropriate. The
   * DAO does not get the ID of the contact being removed -- only the activity
   * ID and the record type -- so what we do here is fetch all the contacts
   * associated with the activity and flag them to be inspected later.
   *
   * @see CRM_Partneraccess_Listener_ActivityContact::handlePostDelete().
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   * @return void
   */
  public static function handlePreDelete(\Symfony\Component\EventDispatcher\Event $event) {
    if (isset($event->object)) {
      try {
        $activity = self::fetchVolunteerActivity($event->object);
      }
      catch (Exception $ex) {
        // An exception means it wasn't an ActivityContact of type Volunteer; bail out.
        return;
      }

      $volunteersByPartner = self::keyVolunteersByPartner($activity);
      if (!empty($volunteersByPartner)) {
        $event = new CRM_Partneraccess_Event_ActivityContact_Deleted($volunteersByPartner);
        self::deferHandling($event);
      }
    }
  }

  /**
   * Handler for ActivityContact delete events.
   *
   * Inspects the contacts associated with a recently updated Activity after the
   * dust settles (@see CRM_Partneraccess_Listener_ActivityContact::handlePreDelete()).
   * If the associated volunteers no longer have Volunteer Activities with the
   * associated partners, they are removed from the partner's volunteer group.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   * @return void
   */
  public static function handlePostDelete(\Symfony\Component\EventDispatcher\Event $event) {
    if (!is_a($event, 'CRM_Partneraccess_Event_ActivityContact_Deleted')) {
      return;
    }

    foreach ($event->volunteersKeyedByPartners as $partnerId => $volunteerIds) {
      foreach ($volunteerIds as $volunteerId) {
        if (!self::hasVolunteerActivityWith($volunteerId, $partnerId)) {
          CRM_Partneraccess_GroupMembershipManager::remove($volunteerId, self::$volGroupType, $partnerId);
        }
      }
    }
  }

}
