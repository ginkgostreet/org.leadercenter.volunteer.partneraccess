<?php

/**
 * @param array $params
 * @return array
 * @see civicrm_api3_create_success
 * @throws API_Exception
 */
function civicrm_api3_partneraccess_processeventqueue($params) {
  $queueManager = new CRM_Queue_Queue_Sql(array(
    'name' => 'org.leadercenter.volunteer.partneraccess',
  ));

  $queueLength = $queueManager->numberOfItems();
  for ($i = 0; $i < $queueLength; $i++) {
    $queueItem = $queueManager->claimItem();
    if ($queueItem !== FALSE) {
      $event = $queueItem->data;
      \Civi::service('dispatcher')->dispatch('partnerAccess.deferredEvent', $event);
      $queueManager->deleteItem($queueItem);
    }
  }

  return civicrm_api3_create_success(ts('Processed %1 deferred events', array(1 => $queueLength, 'domain' => 'org.leadercenter.volunteer.partneraccess')));
}
