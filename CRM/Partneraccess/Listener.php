<?php

/**
 * Base event handling class.
 */
class CRM_Partneraccess_Listener {

  /**
   * Writes events to the queue to be raised later.
   *
   * Sometimes an event cannot be handled right away. It may occur in the middle
   * of a transaction (technically or not) that needs to finish before all the
   * data needed to act on the event are available.
   *
   * When such an event is detected, this method can be used to stash it. A cron
   * job will raise it for handling at a later time.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   * @param int $delaySeconds
   *   Event will not be raised until at least this much time passes
   */
  protected static function deferHandling(\Symfony\Component\EventDispatcher\Event $event, $delaySeconds = 1) {
    $queue_item = new CRM_Queue_DAO_QueueItem();
    $queue_item->queue_name = 'org.leadercenter.volunteer.partneraccess';
    $queue_item->submit_time = CRM_Utils_Time::getTime('YmdHis');
    $queue_item->data = serialize($event);
    $queue_item->weight = 0;
    $now = CRM_Utils_Time::getTimeRaw();
    $queue_item->release_time = date('YmdHis', $now + (int) $delaySeconds);
    $queue_item->save();
  }

}
