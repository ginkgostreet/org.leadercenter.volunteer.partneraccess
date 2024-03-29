<?php

require_once 'partneraccess.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function partneraccess_civicrm_config(&$config) {
  _partneraccess_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function partneraccess_civicrm_xmlMenu(&$files) {
  _partneraccess_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function partneraccess_civicrm_install() {
  _partneraccess_civix_civicrm_install();
}

/**
* Implements hook_civicrm_postInstall().
*
* @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
*/
function partneraccess_civicrm_postInstall() {
  _partneraccess_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function partneraccess_civicrm_uninstall() {
  _partneraccess_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function partneraccess_civicrm_enable() {
  _partneraccess_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function partneraccess_civicrm_disable() {
  _partneraccess_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function partneraccess_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _partneraccess_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function partneraccess_civicrm_managed(&$entities) {
  _partneraccess_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function partneraccess_civicrm_caseTypes(&$caseTypes) {
  _partneraccess_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function partneraccess_civicrm_angularModules(&$angularModules) {
_partneraccess_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function partneraccess_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _partneraccess_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_buildForm/
 */
function partneraccess_civicrm_buildForm($formName, &$form) {
  $function = '_' . __FUNCTION__ . '_' . $formName;
  if (is_callable($function)) {
    $function($form);
  }
}

/**
 * (Delegated) Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_buildForm/
 */
function _partneraccess_civicrm_buildForm_CRM_Contact_Form_Search_Advanced(&$form) {
  $form->setDefaults(array(
    'contact_type' => array('Individual__Volunteer'),
  ));
}

/**
 * Implements hook_civicrm_post().
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_post/
 */
function partneraccess_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  $function = '_' . __FUNCTION__ . '_' . $objectName;
  if (is_callable($function)) {
    $function($op, $objectId, $objectRef);
  }
}

/**
 * (Delegated) Implements hook_civicrm_post().
 *
 * Delegates creation/deletion of partner access groups to CRM_Partneraccess_GroupManager.
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_post/
 */
function _partneraccess_civicrm_post_GroupContact($op, $groupId, &$contactIds) {
  // We add an extra return with this if-statement, but it avoids unnecessary
  // lookups in the case of operations we don't care about.
  if (!in_array($op, array('create', 'delete'))) {
    return;
  }

  if ($groupId !== CRM_Partneraccess_Config::singleton()->getPartnerGroupId()) {
    return;
  }

  $action = ($op === 'create' ? 'activate' : 'deactivate');
  foreach ($contactIds as $cid) {
    $groupManager = new CRM_Partneraccess_GroupManager($cid);
    $groupManager->$action();
    $aclManager = new CRM_Partneraccess_AclManager($groupManager);
    $aclManager->$action();
  }
}

/**
 * (Delegated) Implements hook_civicrm_post().
 *
 * Manages membership of contacts in partner staff group based on relationship.
 * While a smart group could typically be used for this, smart groups *can't*
 * be the actor in an ACL rule, so we are left to manage it manually.
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_post/
 */
function _partneraccess_civicrm_post_Relationship($op, $relationshipId, &$relationship) {
  if ($relationship->relationship_type_id !== CRM_Partneraccess_Config::singleton()->getEmploymentRelTypeId()) {
    return;
  }

  $individualId = $relationship->contact_id_a;
  $partnerId = $relationship->contact_id_b;
  if ($op === 'delete' || !$relationship->is_active) {
    CRM_Partneraccess_GroupMembershipManager::remove($individualId, 'varl_partner_access_static_staff', $partnerId);
  }
  else {
    CRM_Partneraccess_GroupMembershipManager::add($individualId, 'varl_partner_access_static_staff', $partnerId);
  }
}

/**
 * Implements hook_civicrm_container().
 *
 * Used to set up listeners for ActivityContact events.
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_container/
 */
function partneraccess_civicrm_container($container) {
  $container->findDefinition('dispatcher')->addMethodCall('addListener', array('civi.dao.postInsert', array('CRM_Partneraccess_Listener_Activity_ActivityContact', 'handleUpsert')));
  $container->findDefinition('dispatcher')->addMethodCall('addListener', array('civi.dao.postUpdate', array('CRM_Partneraccess_Listener_Activity_ActivityContact', 'handleUpsert')));
  $container->findDefinition('dispatcher')->addMethodCall('addListener', array('civi.dao.preDelete', array('CRM_Partneraccess_Listener_Activity_ActivityContact', 'handlePreDelete')));
  $container->findDefinition('dispatcher')->addMethodCall('addListener', array('civi.dao.preDelete', array('CRM_Partneraccess_Listener_Activity_Activity', 'handlePreDelete')));
  $container->findDefinition('dispatcher')->addMethodCall('addListener', array('partnerAccess.deferredEvent', array('CRM_Partneraccess_Listener_Activity_ActivityContact', 'handleDelete')));
  $container->findDefinition('dispatcher')->addMethodCall('addListener', array('partnerAccess.retroactivate', array('CRM_Partneraccess_Listener_Activity_ActivityContact', 'handleUpsert')));
}
