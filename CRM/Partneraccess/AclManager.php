<?php

/**
 * Manages partner-specific ACL rules.
 */
class CRM_Partneraccess_AclManager {

  /**
   * Provides access to a partner's access groups.
   *
   * @var CRM_Partneraccess_GroupManager
   */
  private $groupManager;

  /**
   * @var mixed
   *   Value of the volunteer manager role for this partner (as specified by
   *   the $groupManager).
   */
  private $volMgrRoleId;

  public function __construct(CRM_Partneraccess_GroupManager $groupManager) {
    $this->groupManager = $groupManager;
  }

  /**
   * Convenience wrapper around all the tasks for activating/creating an ACL
   * rule and its dependencies.
   */
  public function activate() {
    $this->toggleRole(TRUE);
    $this->toggleActor(TRUE);
    $this->toggleRule(TRUE);
  }

  /**
   * Convenience wrapper around all the tasks for deactivating an ACL
   * rule and its dependencies.
   */
  public function deactivate() {
    $this->toggleRole(FALSE);
    $this->toggleActor(FALSE);
    $this->toggleRule(FALSE);
  }

  /**
   * @param mixed $enable
   *   If truthy, creates (or re-enables) the association between a partner's
   *   staff group and the ACL role for the partner's volunteer managers; else,
   *   disables it.
   */
  private function toggleActor($enable = TRUE) {
    $params = array(
      'acl_role_id' => $this->volMgrRoleId,
      'entity_table' => 'civicrm_group',
      'entity_id' => $this->groupManager->getGroupIdByType('varl_partner_access_static_staff'),
    );
    $read = civicrm_api3('AclRole', 'get', $params);
    if ((int) $read['count'] === 1) {
      $params['id'] = $read['id'];
    }
    $params['is_active'] = $enable ? 1 : 0;
    civicrm_api3('AclRole', 'create', $params);
  }

  /**
   * @param mixed $enable
   *   If truthy, enables the ACL role, creating it if it doesn't exist; else,
   *   disables it.
   */
  private function toggleRole($enable = TRUE) {
    $params = array(
      'name' => "Auto-generated (vol mgrs for {$this->groupManager->getPartnerId()})",
      'option_group_id' => 'acl_role',
      'sequential' => 1,
    );
    $read = civicrm_api3('OptionValue', 'get', $params);
    if ((int) $read['count'] === 1) {
      $params['id'] = $read['id'];
    }
    $params['is_active'] = $enable ? 1 : 0;
    $write = civicrm_api3('OptionValue', 'create', $params);

    // OptionValue create returns the value if the option is newly added, but
    // not if it is updated. Fall back to the fetched value in the latter case.
    $this->volMgrRoleId = $write['values'][0]['value'] ? : $read['values'][0]['value'];
  }

  /**
   * @param mixed $enable
   *   If truthy, creates (or re-enables) the ACL rule that allows the partner's
   *   volunteer managers to view its emailable volunteers; else, disables it.
   */
  private function toggleRule($enable = TRUE) {
    // Not all of these params are intuitive, but this is what you get if you
    // create the desired rule via the UI.
    $params = array(
      'name' => "View allowed vols for {$this->groupManager->getPartnerId()}",
      'deny' => 0,
      'entity_table' => 'civicrm_acl_role',
      'entity_id' => $this->volMgrRoleId,
      'operation' => 'View',
      'object_table' => 'civicrm_saved_search',
      'object_id' => $this->groupManager->getGroupIdByType('varl_partner_access_smart_emailable'),
    );
    $read = civicrm_api3('Acl', 'get', $params);
    if ((int) $read['count'] === 1) {
      $params['id'] = $read['id'];
    }
    $params['is_active'] = $enable ? 1 : 0;
    civicrm_api3('Acl', 'create', $params);
  }

}
