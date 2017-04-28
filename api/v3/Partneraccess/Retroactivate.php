<?php

function _civicrm_api3_partneraccess_retroactivate_spec(&$params) {
  $params['partner_id'] = array(
    'api.required' => 1,
    'description' => 'The contact ID of the partner whose groups and ACLs to
       activate/populate.',
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * @param array $params
 * @return array
 * @see civicrm_api3_create_success
 * @throws API_Exception
 */
function civicrm_api3_partneraccess_retroactivate($params) {
  $groupManager = new CRM_Partneraccess_GroupManager($params['partner_id']);
  $groupManager->activate();
  $aclManager = new CRM_Partneraccess_AclManager($groupManager);
  $aclManager->activate();

  $relationships = civicrm_api3('Relationship', 'get', array(
    'relationship_type_id' => CRM_Partneraccess_Config::singleton()->getEmploymentRelTypeId(),
    'options' => array('limit' => 0),
    'is_active' => 1,
    'contact_id_b' => $params['partner_id'],
  ));
  foreach ($relationships['values'] as $r) {
    if ($r['is_active']) {
      CRM_Partneraccess_GroupMembershipManager::add($r['contact_id_a'], 'varl_partner_access_static_staff', $r['contact_id_b']);
    }
  }

  $activityContacts = civicrm_api3('ActivityContact', 'get', array(
    'activity_id.activity_type_id' => 'Volunteer',
    'contact_id' => $params['partner_id'],
    // we only need one half of the relationship; the rest is looked up by the event handler
    'record_type_id' => 'Activity Targets',
    'return' => array('activity_id'),
  ));
  foreach ($activityContacts['values'] as $ac) {
    // the event handler expects a DAO object, so though it's not exactly efficient,
    // we comply
    $activityContact = CRM_Activity_BAO_ActivityContact::findById($ac['activity_id']);
    $event = new \Civi\Core\DAO\Event\PostUpdate($activityContact);
    \Civi::service('dispatcher')->dispatch('partnerAccess.retroactivate', $event);
  }

  return civicrm_api3_create_success(ts('Processed %1 partner staffers and %2 activities', array(1 => $relationships['count'], 2 => $activityContacts['count'], 'domain' => 'org.leadercenter.volunteer.partneraccess')));
}
