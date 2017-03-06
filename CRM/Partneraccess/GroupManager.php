<?php

/**
 * Manages creation/deletion of partner-specific groups used in access control.
 */
class CRM_Partneraccess_GroupManager {

  /**
   * Provides access to extension config.
   *
   * @var CRM_Partneraccess_Config
   */
  private $config;

  /**
   * API-suitable field name for the partner_id custom field.
   *
   * @var string
   */
  private $customFieldName;

  /**
   * @var array
   *   A registry of groups associated with this partner, keyed by type. Note:
   *   it is assumed that only one group of each type can exist per partner.
   */
  private $groups = array();

  /**
   * @var type
   *   An int-like string representing the ID of the group which contains all
   *   partner-specific access groups.
   */
  private $parentGroupId;

  /**
   * The contact ID of the partner whose groups are being managed.
   *
   * @var mixed
   *   Int or int-like string.
   */
  private $partnerId;

  public function __construct($partnerId) {
    $this->partnerId = $partnerId;
    $this->config = CRM_Partneraccess_Config::singleton();
    $this->customFieldName = $this->config->getPartnerCustomFieldApiName();
    $this->parentGroupId = $this->config->getParentGroupId();
  }

  /**
   * Checks for the existence of a partner group.
   *
   * First checks the object cache, which assumes that only one group of each
   * type can exist per partner. Failing that queries the API.
   *
   * @param array $group
   * @return boolean
   */
  public function groupExists(array $group) {
    $type = CRM_Utils_Array::value('group_type', $group);
    if (!empty($this->groups[$type])) {
      return TRUE;
    }

    return (bool) civicrm_api3('Group', 'getcount', $group);
  }

  /**
   * Creates each partner group if it doesn't already exist, else enables it.
   */
  public function activate() {
    foreach ($this->config->getGroupTypes('static') as $type) {
      $params = array(
        $this->customFieldName => $this->partnerId,
        'group_type' => $type,
        'parents' => $this->parentGroupId,
      );

      if ($this->groupExists($params)) {
        $params['api.Group.create'] = array(
          'is_active' => 1,
        );
        civicrm_api3('Group', 'get', $params);
      }
      else {
        // title is a required field
        $params['title'] = "Auto-generated ($type: {$this->partnerId})";
        civicrm_api3('Group', 'create', $params);
      }
    }
  }

  /**
   * Disables each partner group.
   */
  public function deactivate() {
    civicrm_api3('Group', 'get', array(
      $this->customFieldName => $this->partnerId,
      'group_type' => array('IN' => $this->config->getGroupTypes()),
      'api.Group.create' => array(
        'is_active' => 0,
      ),
    ));
  }

}
