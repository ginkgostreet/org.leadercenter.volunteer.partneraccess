<?php

/**
 * A utility class for retrieving extension-related configurations. These
 * configurations don't change at runtime, so we use a singleton architecture
 * to maximize caching and reduce redundant lookups.
 */
class CRM_Partneraccess_Config {

  /**
   * We only need one instance of this object, so we use the singleton
   * pattern and cache the instance in this variable.
   *
   * @var CRM_Partneraccess_Config
   */
  private static $_singleton = NULL;

  /**
   * @var string
   *   An int-like string representing the ID of the employement relationship type.
   */
  private $employmentRelTypeId = NULL;

  /**
   * This extension's custom group types, keyed by prefix.
   *
   * @see getGroupTypes().
   *
   * @var array
   */
  private $groupTypes = array();

  /**
   * @var string
   *   An int-like string representing the ID of the group which contains all
   *   partner-specific access groups.
   */
  private $parentGroupId = NULL;

  /**
   * The API-usable name for the partner field added to groups.
   *
   * @var string
   *   'custom_$n' where $n is the custom field ID
   */
  private $partnerCustomFieldApiName = NULL;

  /**
   * @var string
   *   An int-like string representing the ID of the group which denotes a
   *   contact is a partner.
   */
  private $partnerGroupId = NULL;

  /**
   * @var string
   *   An int-like string representing the identifier (the value, in this case,
   *   since group types are modeled as an optionGroup) of the group type which
   *   represents an organization's staff.
   */
  private $staffGroupTypeValue = NULL;

  /**
   * The point of declaring this is to make it private, so that only the
   * singleton method can be used to instantiate it.
   */
  private function __construct() {
    // nothing to do here, really...
  }

  /**
   * Singleton method used to manage this object.
   *
   * @return CRM_Partneraccess_Config
   */
  static public function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Partneraccess_Config();
    }
    return self::$_singleton;
  }

  /**
   * A simple getter.
   *
   * @see class member employmentRelId.
   */
  public function getEmploymentRelTypeId() {
    if (!isset($this->employmentRelTypeId)) {
      $this->employmentRelTypeId = civicrm_api3('RelationshipType', 'getvalue', array(
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        'is_active' => 1,
        'is_reserved' => 1,
        'name_a_b' => 'Employee of',
        'name_b_a' => 'Employer of',
        'return' => 'id',
      ));
    }
    return $this->employmentRelTypeId;
  }

  /**
   * Retrieves the custom group types by prefix.
   *
   * @param string $prefix
   *   Custom group types are named "varl_partner_access_$prefix_$name." As of
   *   this writing, 'static' and 'smart' are in use. A NULL $prefix will return
   *   all groupTypes.
   * @return array
   *   Array of machine names for group type options.
   */
  public function getGroupTypes($prefix = NULL) {
    if (empty($this->groupTypes)) {
      $api = civicrm_api3('OptionValue', 'get', array(
        'name' => array('LIKE' => 'varl_partner_access_%'),
        'option_group_id' => 'group_type',
        'return' => 'name',
      ));

      foreach ($api['values'] as $v) {
        $groupType = $v['name'];
        $parts = explode('_', $groupType);
        $key = $parts[3];
        $this->groupTypes[$key][] = $groupType;
      }
    }

    if ($prefix === NULL) {
      $result = array();
      foreach ($this->groupTypes as $key => $value) {
        $result = array_merge($result, $value);
      }
    }
    else {
      $result = CRM_Utils_Array::value($prefix, $this->groupTypes, array());
    }
    return $result;
  }

  /**
   * A simple getter.
   *
   * TODO: The getGroupTypes method could probably have been modified to serve
   * this purpose, but I'm loathe to do more refactoring than I need to at this
   * time.
   *
   * @see class member staffGroupTypeValue.
   */
  public function getStaffGroupTypeValue() {
    if (!isset($this->staffGroupTypeValue)) {
      $this->staffGroupTypeValue = civicrm_api3('OptionValue', 'getvalue', array(
        'return' => 'value',
        'option_group_id' => 'group_type',
        'name' => 'varl_partner_access_static_staff',
      ));
    }
    return $this->staffGroupTypeValue;
  }

  /**
   * A simple getter.
   *
   * @see class member parentGroupId.
   */
  public function getParentGroupId() {
    if (!isset($this->parentGroupId)) {
      $this->parentGroupId = civicrm_api3('Group', 'getvalue', array(
        'name' => 'varl_partner_access_parent_group',
        'return' => 'id',
      ));
    }
    return $this->parentGroupId;
  }

  /**
   * Get API-suitable field name for the partner_id custom field.
   *
   * @return string
   *   @see partnerCustomFieldApiName
   */
  public function getPartnerCustomFieldApiName() {
    if (!isset($this->partnerCustomFieldApiName)) {
      $this->partnerCustomFieldApiName = 'custom_' . civicrm_api3('CustomField', 'getvalue', array(
            'return' => 'id',
            'custom_group_id' => 'Volunteer_Arlington_Partner_Access',
            'name' => 'partner_id',
      ));
    }
    return $this->partnerCustomFieldApiName;
  }

  /**
   * A simple getter.
   *
   * @see class member parentGroupId.
   */
  public function getPartnerGroupId() {
    if (!isset($this->partnerGroupId)) {
      $this->partnerGroupId = civicrm_api3('Group', 'getvalue', array(
        'return' => 'id',
        // hardcoded as this already exists in production
        'name' => 'Nonprofit_Partners_Active_11',
      ));
    }
    return $this->partnerGroupId;
  }

}
