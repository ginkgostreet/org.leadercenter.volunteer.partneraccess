<?php

/**
 * See "hook_civicrm_managed" (at
 * https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_managed/) as well
 * as "API and the Art of Installation" (at
 * https://civicrm.org/blogs/totten/api-and-art-installation).
 */

$adminVisibility = civicrm_api3('OptionValue', 'getvalue', array(
  'name' => 'admin',
  'option_group_id' => 'visibility',
  'return' => 'value',
));

return array(
  array(
    'module' => 'org.leadercenter.volunteer.partneraccess',
    'name' => 'Volunteer Arlington Partner Access - Optin Group Type',
    'entity' => 'OptionValue',
    'params' => array(
      'description' => ts('Groups of this type represent contacts who have manually opted in to receive communications from the group creator.', array('org.leadercenter.volunteer.partneraccess')),
      'is_reserved' => 1,
      'label' => 'Volunteer Arlington Partner Access - Optin',
      'name' => 'varl_partner_access_optin',
      'option_group_id' => 'group_type',
      'version' => 3,
      'visibility_id' => $adminVisibility,
    ),
  ),
  array(
    'module' => 'org.leadercenter.volunteer.partneraccess',
    'name' => 'Volunteer Arlington Partner Access - Optout Group Type',
    'entity' => 'OptionValue',
    'params' => array(
      'description' => ts('Groups of this type represent contacts who have manually opted out of receiving communications from the group creator. For the purposes of access control, membership in this group trumps membership in any other.', array('org.leadercenter.volunteer.partneraccess')),
      'is_reserved' => 1,
      'label' => 'Volunteer Arlington Partner Access - Optout',
      'name' => 'varl_partner_access_optout',
      'option_group_id' => 'group_type',
      'version' => 3,
      'visibility_id' => $adminVisibility,
    ),
  ),
  array(
    'module' => 'org.leadercenter.volunteer.partneraccess',
    'name' => 'Volunteer Arlington Partner Access - Staff Group Type',
    'entity' => 'OptionValue',
    'params' => array(
      'description' => ts('Groups of this type represent contacts who are active staff of the group creator.', array('org.leadercenter.volunteer.partneraccess')),
      'is_reserved' => 1,
      'label' => 'Volunteer Arlington Partner Access - Staff',
      'name' => 'varl_partner_access_staff',
      'option_group_id' => 'group_type',
      'version' => 3,
      'visibility_id' => $adminVisibility,
    ),
  ),
  array(
    'module' => 'org.leadercenter.volunteer.partneraccess',
    'name' => 'Volunteer Arlington Partner Access - Volunteer Group Type',
    'entity' => 'OptionValue',
    'params' => array(
      'description' => ts('Groups of this type represent contacts who have volunteered with the group creator.', array('org.leadercenter.volunteer.partneraccess')),
      'is_reserved' => 1,
      'label' => 'Volunteer Arlington Partner Access - Volunteer',
      'name' => 'varl_partner_access_volunteer',
      'option_group_id' => 'group_type',
      'version' => 3,
      'visibility_id' => $adminVisibility,
    ),
  ),
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
