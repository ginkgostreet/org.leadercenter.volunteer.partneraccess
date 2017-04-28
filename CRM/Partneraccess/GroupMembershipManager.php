<?php

/**
 * Manages membership of contacts in partner-specific groups used for access control.
 */
class CRM_Partneraccess_GroupMembershipManager {

  /**
   * Convenience wrapper around self::manage().
   */
  public static function add($contactId, $groupType, $partnerId) {
    self::manage('Added', $contactId, $groupType, $partnerId);
  }

  /**
   * Convenience wrapper around self::manage().
   */
  public static function remove($contactId, $groupType, $partnerId) {
    self::manage('Removed', $contactId, $groupType, $partnerId);
  }

  /**
   * Manages a contact's membership in a partner group.
   *
   * @param string $action
   *   See spec for api.GroupContact.create, field "status."
   * @param mixed $contactId
   *   Int or int-like string representing the contact whose group membership is
   *   being managed.
   * @param string $groupType
   *   See Group.mgd.php.
   * @param mixed $partnerId
   *   Int or int-like string representing the partner to which the contacts in
   *   the group are related.
   */
  private static function manage($action, $contactId, $groupType, $partnerId) {
    $group = self::getPartnerGroup(array(
      'group_type' => $groupType,
      'partner_id' => $partnerId,
    ));

    if (!empty($group['name'])) {
      civicrm_api3('GroupContact', 'create', array(
        'contact_id' => $contactId,
        'group_id' => $group['name'],
        'status' => $action,
      ));
    }
  }

  /**
   * Retrieves a partner group.
   *
   * TODO: Consider moving this to a class that is about groups rather than
   * group membership.
   *
   * @param array $params
   *   To allow fetching by other criteria in the future, we accept a keyed array.
   *   Presently the following keys are required:
   *     - group_type - See Group.mgd.php.
   *     - partner_id - The contact ID of the partner to which the contacts in
   *                    the group are related.
   * @return array
   *   Array containing the properties of the single matching partner group.
   *   Array is empty if a single matching group could not be found.
   */
  public static function getPartnerGroup($params) {
    $groupType = CRM_Utils_Array::value('group_type', $params);
    $partnerId = CRM_Utils_Array::value('partner_id', $params);
    if (!isset($groupType, $partnerId)) {
      CRM_Core_Error::fatal("Missing required parameter 'group_type' and/or 'partner_id.'");
    }

    $config = CRM_Partneraccess_Config::singleton();
    $customFieldName = $config->getPartnerCustomFieldApiName();
    $parentGroupId = $config->getParentGroupId();

    $fetchParams = array(
      $customFieldName => $partnerId,
      'group_type' => $groupType,
      'parents' => $parentGroupId,
    );
    $fetch = CRM_Partneraccess_Polyfill::apiGroupGet($fetchParams);
    return ($fetch['count'] == 1) ? $fetch['values'] : array();
  }

}
