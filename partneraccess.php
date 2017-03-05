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
 * Manages creation/deletion of partner-specific groups used in access control.
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_post/
 */
function _partneraccess_civicrm_post_GroupContact($op, $groupId, &$contactIds) {
  // We add an extra return with this if-statement, but it avoids unnecessary
  // lookups in the case of operations we don't care about.
  if (!in_array($op, array('create', 'delete'))) {
    return;
  }

  $partnerGroupId = civicrm_api3('Group', 'getvalue', array(
    'return' => 'id',
    // hardcoded as this already exists in production
    'name' => 'Nonprofit_Partners_Active_11',
  ));
  if ($groupId !== $partnerGroupId) {
    return;
  }

  $helper = ($op === 'create' ? 'activatePartnerStaticGroup' : 'deactivatePartnerStaticGroup');
  $result = civicrm_api3('OptionValue', 'get', array(
    'name' => array('LIKE' => 'varl_partner_access_static_%'),
    'option_group_id' => 'group_type',
    'return' => 'name',
  ));
  foreach ($contactIds as $cid) {
    foreach ($result['values'] as $v) {
      $groupType = $v['name'];
      $helper($cid, $groupType);
    }
  }
}

/**
 * Creates partner group if it doesn't already exist, else enables it.
 *
 * @param mixed $partnerId
 *   Int or int-like string representing the contact ID.
 * @param string $type
 *   A group type; see optionGroup group_type.
 */
function activatePartnerStaticGroup($partnerId, $type) {
  $params = array(
    getPartnerCustomFieldName() => $partnerId,
    'group_type' => $type,
    'parents' => getParentGroupId(),
  );

  $exists = civicrm_api3('Group', 'getcount', $params);
  if ($exists) {
    $params['api.Group.create'] = array(
      'is_active' => 1,
    );
    civicrm_api3('Group', 'get', $params);
  }
  else {
    // title is a required field
    $params['title'] = "Auto-generated $type for contact ID $partnerId";
    civicrm_api3('Group', 'create', $params);
  }

}

/**
 * Disables partner group if it exists.
 *
 * @param mixed $partnerId
 *   Int or int-like string representing the contact ID.
 * @param string $type
 *   A group type; see optionGroup group_type.
 */
function deactivatePartnerStaticGroup($partnerId, $type) {
  civicrm_api3('Group', 'get', array(
    getPartnerCustomFieldName() => $partnerId,
    'group_type' => $type,
    'api.Group.create' => array(
      'is_active' => 0,
    ),
  ));
}

/**
 * @staticvar mixed $id
 *   See return.
 * @return string
 *   An int-link string representing the ID of the group which contains all
 *   partner-specific access groups.
 */
function getParentGroupId() {
  static $id;
  if (empty($id)) {
    $id = civicrm_api3('Group', 'getvalue', array(
      'name' => 'varl_partner_access_parent_group',
      'return' => 'id',
    ));
  }
  return $id;
}

/**
 * Get API-suitable field name for the partner_id custom field.
 *
 * @staticvar string $name
 * @return string
 */
function getPartnerCustomFieldName() {
  static $name;
  if (empty($name)) {
    $name = 'custom_' . civicrm_api3('CustomField', 'getvalue', array(
          'return' => 'id',
          'custom_group_id' => 'Volunteer_Arlington_Partner_Access',
          'name' => 'partner_id',
    ));
  }
  return $name;
}
