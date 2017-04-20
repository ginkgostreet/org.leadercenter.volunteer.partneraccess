<?php

/**
 * Handle DAO events for the ActivityContact entity type.
 */
class CRM_Partneraccess_Listener_Activity_ActivityContact extends CRM_Partneraccess_Listener_Activity {

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
   * @see CRM_Partneraccess_Listener_Activity_ActivityContact::handlePostDelete().
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
   * dust settles (@see CRM_Partneraccess_Listener_Activity_ActivityContact::handlePreDelete()).
   * If the associated volunteers no longer have Volunteer Activities with the
   * associated partners, they are removed from the partner's volunteer group.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   * @return void
   */
  public static function handleDelete(\Symfony\Component\EventDispatcher\Event $event) {
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
