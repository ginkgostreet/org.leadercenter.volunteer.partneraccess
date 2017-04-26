<?php

// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_managed/
return array (
  0 =>
  array (
    'name' => 'Cron:Partneraccess.retroactivate',
    'entity' => 'Job',
    'params' =>
    array (
      'version' => 3,
      'is_active' => 0,
      'name' => 'Partner Access Retroactivate',
      'description' => ts("Retroactively creates and populates groups and ACLs used in managing partners' access to volunteers. This is necessary to properly represent the history of site activity prior to deployment of this extension.", array('org.leadercenter.volunteer.partneraccess')),
      'run_frequency' => 'Yearly',
      'api_entity' => 'Partneraccess',
      'api_action' => 'retroactivate',
      'parameters' => '',
    ),
  ),
);