<?php

/**
 *  * This is a helper class for the queue functionality.
 *  * It is a singleton class because it will hold the queue object for our extension
 *  *
 *  *
 .*/

/**
 * 
 */
class CRM_OSDIQueue_Helper {
  const QUEUE_NAME = 'com.civicrm.osdiqueue';
  private $queue;
  static $singleton;

  /**
   *
   */
  private function __construct() {
    $this->queue = CRM_Queue_Service::singleton()->create(array(
      'type' => 'Sql',
      'name' => self::QUEUE_NAME,
    // Do not flush queue upon creation.
      'reset' => FALSE,
    ));
  }

  /**
   *
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new CRM_OSDIQueue_Helper();
    }
    return self::$singleton;
  }

  /**
   *
   */
  public function getQueue() {
    return $this->queue;
  }

}
