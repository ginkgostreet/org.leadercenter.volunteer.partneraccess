<?php

class CRM_Partneraccess_Form_Report_PartnerVolunteerReport extends CRM_Volunteer_Form_VolunteerReport {

  /**
   * Filters the results to only those activities which are targeted at a partner
   * which has the current user in its varl_partner_access_static_staff group.
   *
   * @param type $tableAlias
   *   Not used but included since failing to keep the signature consistent with
   *   the overriden method results in warnings.
   */
  function buildACLClause($tableAlias = 'contact_a') {
    $this->_aclFrom = $this->_aclWhere = NULL;
    if (CRM_Core_Permission::check('view all contacts')) {
      return;
    }

    // TODO: it's the Config class's responsibility to fetch static metadata,
    // but I guess if we're only going to do this once it's okay...
    $api = civicrm_api3('CustomField', 'get', array(
      'custom_group_id' => 'Volunteer_Arlington_Partner_Access',
      'name' => 'partner_id',
      'return' => array('custom_group_id.table_name', 'column_name'),
      'sequential' => 1,
    ));
    $customTable = $api['values'][0]['custom_group_id.table_name'];
    $partnerIdColumn = $api['values'][0]['column_name'];

    // TODO: this should probably be handled in the Config class also
    $groupTypeValue = civicrm_api3('OptionValue', 'getvalue', array(
      'return' => "value",
      'option_group_id' => "group_type",
      'name' => "varl_partner_access_static_staff",
    ));
    $groupTypePattern = "'%" . CRM_CORE_DAO::VALUE_SEPARATOR . $groupTypeValue . CRM_CORE_DAO::VALUE_SEPARATOR . "%'";

    $contactID = CRM_Core_Session::singleton()->get('userID');
    $this->_aclFrom = "
             INNER JOIN $customTable partner_access_custom
                    ON activity_target_civireport.contact_id = partner_access_custom.$partnerIdColumn
             INNER JOIN civicrm_group partner_access_group
                    ON partner_access_group.id = partner_access_custom.entity_id
                    AND partner_access_group.group_type LIKE $groupTypePattern
             INNER JOIN civicrm_group_contact partner_access_group_contact
                   ON partner_access_group_contact.group_id = partner_access_group.id
                   AND partner_access_group_contact.status = 'Added'
                   AND partner_access_group_contact.contact_id = $contactID ";
  }

}
