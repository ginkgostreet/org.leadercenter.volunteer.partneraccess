<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'CRM_Partneraccess_Form_Report_PartnerVolunteerReport',
    'entity' => 'ReportTemplate',
    'params' => 
    array (
      'version' => 3,
      'label' => 'PartnerVolunteerReport',
      'description' => 'PartnerVolunteerReport (org.leadercenter.volunteer.partneraccess)',
      'class_name' => 'CRM_Partneraccess_Form_Report_PartnerVolunteerReport',
      'report_url' => 'partner/volunteer',
      'component' => '',
    ),
  ),
);