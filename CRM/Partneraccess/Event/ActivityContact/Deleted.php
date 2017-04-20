<?php

/**
 * Class CRM_Partneraccess_Event_ActivityContact_Deleted
 */
class CRM_Partneraccess_Event_ActivityContact_Deleted extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @array
   *   An array of arrays. Volunteer contact IDs keyed by partner contact ID.
   */
  public $volunteersKeyedByPartners;

  /**
   * @param $volunteersKeyedByPartners
   */
  public function __construct($volunteersKeyedByPartners) {
    $this->volunteersKeyedByPartners = $volunteersKeyedByPartners;
  }

}
