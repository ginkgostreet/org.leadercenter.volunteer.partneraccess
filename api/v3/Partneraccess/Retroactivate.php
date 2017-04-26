<?php

/**
 * @param array $params
 * @return array
 * @see civicrm_api3_create_success
 * @throws API_Exception
 */
function civicrm_api3_partneraccess_retroactivate($params) {
  $config = CRM_Partneraccess_Config::singleton();

  $groupContacts = civicrm_api3('GroupContact', 'get', array(
    'return' => array('contact_id'),
    'status' => "Added",
    'group_id' => $config->getPartnerGroupId(),
    'options' => array('limit' => 0),
  ));
  $partners = array_column($groupContacts['values'], 'contact_id');
  foreach ($partners as $contactId) {
    $groupManager = new CRM_Partneraccess_GroupManager($contactId);
    $groupManager->activate();
    $aclManager = new CRM_Partneraccess_AclManager($groupManager);
    $aclManager->activate();
  }

  $relationships = civicrm_api3('Relationship', 'get', array(
    'relationship_type_id' => $config->getEmploymentRelTypeId(),
    'options' => array('limit' => 0),
    'contact_id_b' => array('IN' => $partners),
  ));
  foreach ($relationships['values'] as $r) {
    if ($r['is_active']) {
      CRM_Partneraccess_GroupMembershipManager::add($r['contact_id_a'], 'varl_partner_access_static_staff', $r['contact_id_b']);
    }
  }

  $activityContacts = civicrm_api3('ActivityContact', 'get', array(
    'activity_id.activity_type_id' => 'Volunteer',
    // we only need one half of the relationship; the rest is looked up by the event handler
    'record_type_id' => 'Activity Assignees',
    'return' => array('activity_id'),
  ));
  foreach ($activityContacts['values'] as $ac) {
    // the event handler expects a DAO object, so though it's not exactly efficient,
    // we comply
    $activityContact = CRM_Activity_BAO_ActivityContact::findById($ac['activity_id']);
    $event = new \Civi\Core\DAO\Event\PostUpdate($activityContact);
    \Civi::service('dispatcher')->dispatch('partnerAccess.retroactivate', $event);
  }

  return civicrm_api3_create_success(ts('Processed %1 partners, %2 partner staffers, and %3 activities', array(1 => $groupContacts['count'], 2 => $relationships['count'], 3 => $activityContacts['count'], 'domain' => 'org.leadercenter.volunteer.partneraccess')));
}
