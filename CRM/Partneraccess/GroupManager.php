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

  /**
   * @var array
   *   A registry of saved search IDs associated with this partner, keyed by group
   *   type. Note: this member is populated only in a create (not edit) workflow.
   */
  private $savedSearches = array();

  public function __construct($partnerId) {
    $this->partnerId = $partnerId;
    $this->config = CRM_Partneraccess_Config::singleton();
    $this->customFieldName = $this->config->getPartnerCustomFieldApiName();
    $this->parentGroupId = $this->config->getParentGroupId();
  }

  /**
   * First checks the object cache. Failing that queries the API. Assumes that
   * only one group of each type can exist per partner.
   *
   * @param string $type
   *   @see option group group_type for valid values.
   * @return mixed
   *   Int-like string ID if found; NULL if doesn't exist.
   */
  public function getGroupIdByType($type) {
    $result = CRM_Utils_Array::value($type, $this->groups);
    if (empty($result)) {
      $params = array(
        $this->customFieldName => $this->partnerId,
        'parents' => $this->parentGroupId,
        'group_type' => $type,
      );
      $fetch = CRM_Partneraccess_Polyfill::apiGroupGet($params);
      $result = CRM_Utils_Array::value('id', $fetch);
    }

    return $result;
  }

  public function getPartnerId() {
    return $this->partnerId;
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
      $this->activateSingle($type);
    }

    $this->activateSmartGroup();
  }

  /**
   * Creates the partner group specified by type if it doesn't already exist,
   * else enables it.
   *
   * @param string $type
   *   @see option group group_type for valid values.
   */
  private function activateSingle($type) {
    $typeParam = self::groupTypeIsAclActor($type) ? array($type, 'Access Control') : array($type);
    $params = array(
      $this->customFieldName => $this->partnerId,
      'group_type' => $typeParam,
      'is_active' => 1,
      'parents' => $this->parentGroupId,
    );

    $groupId = $this->getGroupIdByType($type);
    if ($groupId) {
      // force an update
      $params['id'] = $groupId;
    }
    else {
      // title is a required field
      $params['title'] = "Auto-generated ($type: {$this->partnerId})";

      // for creates only, make the group "smart" by passing in the saved search param if one exists
      $savedSearchId = CRM_Utils_Array::value($type, $this->savedSearches);
      if ($savedSearchId) {
        // Hmm, this is inconsistent with the other groups we create. Can't think
        // of a reason the group ought to be reserved, but conservatively leaving it.
        $params['is_reserved'] = 1;
        // VARL-265: passing the saved_search_id on initial create populates the
        // group's where_clause, select_tables, and where_tables fields properly.
        $params['saved_search_id'] = $savedSearchId;
      }
    }
    $api = civicrm_api3('Group', 'create', $params);

    $this->groups[$type] = $api['id'];
  }

  /**
   * Disables each partner group.
   */
  public function deactivate() {
    foreach ($this->config->getGroupTypes() as $type) {
      $groupId = $this->getGroupIdByType($type);
      civicrm_api3('Group', 'create', array(
        'id' => $groupId,
        'is_active' => 0,
      ));
    }
  }

  /**
   * If the smart group exists, enables it. Otherwise, creates a saved search,
   * a new group, and an association between the two.
   */
  private function activateSmartGroup() {
    $type = 'varl_partner_access_smart_emailable';
    $exists = $this->getGroupIdByType($type);

    // VARL-265: if the group doesn't exist at all, we must first create the saved search
    if (!$exists) {
      $this->savedSearches[$type] = $this->createSavedSearch();
    }

    $this->activateSingle($type);
  }

  /**
   * Creates a saved search.
   *
   * The search is an include/exclude for contacts in the volunteer and optin
   * but not the optout group. Does not take responsibility for associating the
   * search with a group.
   *
   * @return string
   *   The ID of the saved search that was just created.
   */
  private function createSavedSearch() {
    // TODO: this CustomSearch lookup belongs in the Config class where it can be cached
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

    return $savedSearch['id'];
  }

}
