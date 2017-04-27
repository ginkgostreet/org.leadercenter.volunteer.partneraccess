<?php

/**
 * Utility methods for dealing with shortcomings in CiviCRM core. Usually the
 * term "polyfill" is reserved for JavaScript, but the etymology resonates, so
 * we'll use it here. The term is based on the spackling paste brand Polyfilla,
 * a paste used to cover up cracks and holes in walls, and the meaning "fill in
 * holes (in functionality) in many (poly-) ways."
 */
class CRM_Partneraccess_Polyfill {

  /**
   * Gross, gross hack to deal with the fact that, when the group_type param is
   * specified, api.Group.get fails to return records where the group_type is
   * multivalue. This method seeks to serve as a transparent wrapper around the
   * broken API. See VARL-256.
   *
   * Note: Assumes group_type will be passed as a scalar, and by name as opposed
   * to value.
   *
   * @param array $params
   *   These are passed to api.Group.get and/or used to filter the results of a
   *   call that would have broken if passed directly.
   */
  public static function apiGroupGet(array $params) {
    $type = CRM_Utils_Array::value('group_type', $params);
    if (!CRM_Partneraccess_GroupManager::groupTypeIsAclActor($type)) {
      return civicrm_api3('Group', 'get', $params);
    }

    // The API returns each group_type as a value (an int-like string) rather
    // than a name, so we must fetch the value for filtering.
    $staffGroupTypeValue = CRM_Partneraccess_Config::singleton()->getStaffGroupTypeValue();

    // Groups which are ACL Actors will have more than one type ($type and
    // 'Access Control') and thus will not be returned properly by the
    // unwrapped API. To perform the lookup we must remove the group_type from
    // the query and filter the results.
    unset($params['group_type']);
    $result = civicrm_api3('Group', 'get', $params);
    foreach ($result['values'] as $key => $item) {
      if (!in_array($staffGroupTypeValue, $item['group_type'])) {
        unset($result['values'][$key]);
      }
    }

    return $result;
  }

}