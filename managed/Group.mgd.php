<?php

/**
 * See "hook_civicrm_managed" (at
 * https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_managed/) as well
 * as "API and the Art of Installation" (at
 * https://civicrm.org/blogs/totten/api-and-art-installation).
 */

return array(
  array(
    'module' => 'org.leadercenter.volunteer.partneraccess',
    'name' => 'Volunteer Arlington Partner Access - Parent Group',
    'entity' => 'Group',
    'params' => array(
      'description' => ts('This group is managed by the Volunteer Arlington Partner Access extension. It houses groups that are used to ensure partners have appropriate access to the system.', array('org.leadercenter.volunteer.partneraccess')),
      'is_reserved' => 1,
      'name' => 'varl_partner_access_parent_group',
      'source' => 'org.leadercenter.volunteer.partneraccess',
      'title' => 'Volunteer Arlington Partner Access - Parent Group',
      'version' => 3,
    ),
  ),
);
