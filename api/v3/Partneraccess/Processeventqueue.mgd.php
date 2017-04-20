<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_managed/
return array (
  0 =>
  array (
    'name' => 'Cron:Partneraccess.processeventqueue',
    'entity' => 'Job',
    'params' =>
    array (
      'version' => 3,
      'is_active' => 1,
      'name' => 'Process partner access event queue',
      'description' => ts("Re-raises deferred events for org.leadercenter.volunteer.partneraccess. Used in managing partners' access to volunteers.", array('org.leadercenter.volunteer.partneraccess')),
      'run_frequency' => 'Always',
      'api_entity' => 'Partneraccess',
      'api_action' => 'processeventqueue',
      'parameters' => '',
    ),
  ),
);