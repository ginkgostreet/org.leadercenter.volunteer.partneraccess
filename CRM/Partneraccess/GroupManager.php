<?php

/**
 * Manages creation/deletion of partner-specific groups used in access control.
 */
class CRM_Partneraccess_GroupManager {

  /**
   * @var array
   *   A registry of groups associated with this partner, keyed by type. Note:
   *   it is assumed that only one group of each type can exist per partner.
   */
  private $groups = array();

  /**
   * The contact ID of the partner whose groups are being managed.
   *
   * @var mixed
   *   Int or int-like string.
   */
  private $partnerId;

  public function __construct($partnerId) {
    $this->partnerId = $partnerId;
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
    $config = CRM_Partneraccess_Config::singleton();
    $fieldName = $config->getPartnerCustomFieldApiName();
    $parentGroupId = $config->getParentGroupId();

    foreach ($config->getGroupTypes('static') as $type) {
      $params = array(
        $fieldName => $this->partnerId,
        'group_type' => $type,
        'parents' => $parentGroupId,
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
  function deactivate() {
    $config = CRM_Partneraccess_Config::singleton();
    $fieldName = $config->getPartnerCustomFieldApiName();

    civicrm_api3('Group', 'get', array(
      $fieldName => $this->partnerId,
      'group_type' => array('IN' => $config->getGroupTypes()),
      'api.Group.create' => array(
        'is_active' => 0,
      ),
    ));
  }

}
