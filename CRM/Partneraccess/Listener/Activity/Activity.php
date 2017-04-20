<?php

/**
 * Handle DAO events for the ActivityContact entity type.
 */
class CRM_Partneraccess_Listener_Activity_Activity extends CRM_Partneraccess_Listener_Activity {

  /**
   * @param CRM_Activity_DAO_Activity $activity
   * @return array
   * @throws CiviCRM_API3_Exception
   *   Throws exception if not a volunteer activity or if actvity not found.
   */
  private static function fetchVolunteerActivity(CRM_Activity_DAO_Activity $activity) {
    return civicrm_api3('Activity', 'getsingle', array(
      'activity_type_id' => 'Volunteer',
      'id' => $activity->id,
      'api.ActivityContact.get' => array(
        'record_type_id' => array('IN' => self::$targetRecordTypes),
      ),
    ));
  }

  /**
   * Handler for Activity DAO pre-delete events.
   *
   * Removes a volunteer from a partner's group if they have no other volunteer
   * activities together.
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
        // An exception means it wasn't an Activity of type Volunteer; bail out.
        return;
      }

      foreach (self::keyVolunteersByPartner($activity) as $partnerId => $volunteerIds) {
        foreach ($volunteerIds as $volunteerId) {
          if (!self::hasVolunteerActivityWith($volunteerId, $partnerId, $activity['id'])) {
            CRM_Partneraccess_GroupMembershipManager::remove($volunteerId, self::$volGroupType, $partnerId);
          }
        }
      }
    }
  }

}
