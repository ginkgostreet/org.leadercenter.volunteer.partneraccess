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
   * @param string $type
   *   @see option group group_type for valid values.
   * @return mixed
   *   Group ID or NULL.
   */
  public function getGroupIdByType($type) {
    $result = CRM_Utils_Array::value($type, $this->groups);
    if (empty($result)) {
      try {
        $result = civicrm_api3('Group', 'getvalue', array(
          $this->customFieldName => $this->partnerId,
          'group_type' => $type,
          'return' => 'id,'
        ));
      }
      catch (Exception $e) {
        // nothing to do here but allow the function to return NULL
      }
    }

    return $result;
  }

  public function getPartnerId() {
    return $this->partnerId;
  }

  /**
   * Checks for the existence of a partner group.
   *
   * First checks the object cache, which assumes that only one group of each
   * type can exist per partner. Failing that queries the API.
   *
   * @param string $type
   * @return boolean
   */
  public function groupExists($type) {
    $result = FALSE;
    if (!empty($this->groups[$type])) {
      $result = TRUE;
    }

    if (!$result) {
      $params = array(
        $this->customFieldName => $this->partnerId,
        'parents' => $this->parentGroupId,
      );

      // Gross, gross hack to deal with the fact that api.Group.get excludes
      // records where the group_type is multivalue when the group_type param
      // is specified.
      if (self::groupTypeIsAclActor($type)) {
        $staffGroupTypeValue = civicrm_api3('OptionValue', 'getvalue', array(
          'return' => 'value',
          'option_group_id' => 'group_type',
          'name' => 'varl_partner_access_static_staff',
        ));

        $api = civicrm_api3('Group', 'get', $params);
        foreach ($api['values'] as $item) {
          if (in_array($staffGroupTypeValue, $item['group_type'])) {
            $result = TRUE;
            break;
          }
        }
      }
      else {
        $params['group_type'] = $type;
        $result = (bool) civicrm_api3('Group', 'getcount', $params);
      }
    }
    return $result;
  }

  /**
   * A simple encapsulation of logic about which group types are to be used as
   * ACL Actors and should thus also be flagged as group type 'Access Control.'
   *
   * @param string $type
   * @return boolean
   */
  public static function groupTypeIsAclActor($type) {
    return $type === 'varl_partner_access_static_staff';
  }

  /**
   * Creates each partner group if it doesn't already exist, else enables it.
   */
  public function activate() {
    foreach ($this->config->getGroupTypes('static') as $type) {
      $typeParam = self::groupTypeIsAclActor($type) ? array($type, 'Access Control') : array($type);
      $params = array(
        $this->customFieldName => $this->partnerId,
        'group_type' => array('IN' => $typeParam),
        'parents' => $this->parentGroupId,
      );

      if ($this->groupExists($type)) {
        $params['api.Group.create'] = array(
          'is_active' => 1,
        );
        $api = civicrm_api3('Group', 'get', $params);
      }
      else {
        // title is a required field
        $params['title'] = "Auto-generated ($type: {$this->partnerId})";
        $api = civicrm_api3('Group', 'create', $params);
      }

      $this->groups[$type] = $api['id'];
    }

    $this->activateSmartGroup();
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

  /**
   * If the smart group exists, enables it. Otherwise, creates a saved search,
   * creates a new group, and associates the two.
   */
  private function activateSmartGroup() {
    $type = 'varl_partner_access_smart_emailable';
    $params = array(
      $this->customFieldName => $this->partnerId,
      'group_type' => $type,
      'parents' => $this->parentGroupId,
    );

    if ($this->groupExists($type)) {
      $params['api.Group.create'] = array(
        'is_active' => 1,
      );
      $api = civicrm_api3('Group', 'get', $params);
    }
    else {
      $customSearchId = civicrm_api3('CustomSearch', 'getvalue', array(
        'name' => 'CRM_Contact_Form_Search_Custom_Group',
        'return' => 'value',
      ));
      $savedSearch = civicrm_api3('SavedSearch', 'create', array(
        'form_values' => array(
          array(
            'csid', // field name
            '=', // operator
            $customSearchId, // "sql filter syntax"
            0, // always 0
            0, // always 0
          ),
          array(
            'entryURL',
            '=',
            CIVICRM_UF_BASEURL . "/civicrm/contact/search/custom?csid={$customSearchId}&reset=1",
            0,
            0,
          ),
          array(
            'includeGroups',
            'IN',
            array(
              $this->groups['varl_partner_access_static_volunteer'],
              $this->groups['varl_partner_access_static_optin'],
            ),
            0,
            0,
          ),
          array(
            'excludeGroups',
            'IN',
            array($this->groups['varl_partner_access_static_optout']),
            0,
            0,
          ),
          array(
            'andOr',
            '=',
            1,
            0,
            0,
          ),
          array(
            'customSearchID',
            '=',
            $customSearchId,
            0,
            0,
          ),
          array(
            'customSearchClass',
            '=',
            'CRM_Contact_Form_Search_Custom_Group',
            0,
            0,
          ),
        ),
        'search_custom_id' => $customSearchId,
      ));

      // workaround for CRM-20222
      $bao = CRM_Contact_BAO_SavedSearch::findById($savedSearch['id']);
      $bao->search_custom_id = $customSearchId;
      $bao->save();
      // end workaround for CRM-20222

      // title is a required field
      $params['title'] = "Auto-generated ($type: {$this->partnerId})";
      $params['saved_search_id'] = $savedSearch['id'];
      $params['is_reserved'] = 1;
      $api = civicrm_api3('Group', 'create', $params);
    }

    $this->groups[$type] = $api['id'];
  }

}
