<?php
 
/**
 * This is a helper class for the queue functionality.
 * It is a singleton class because it will hold the queue object for our extension
 *
 *
 */
class OSDIQueueHelper{
 
  const QUEUE_NAME = 'com.civicrm.osdiqueue';
 
  private $queue;
 
  static $singleton;
  /**
   * @return CRM_Queuehowto_Helper
   */
  public static function singleton() {
    if (!self::$singleton) {
      self::$singleton = new OSDIQueueHelper();
    }
    return self::$singleton;
  }
 
  private function __construct() {
    $this->queue = CRM_Queue_Service::singleton()->create(array(
      'type' => 'Sql',
      'name' => self::QUEUE_NAME,
      'reset' => false, //do not flush queue upon creation
    ));
  }
 
  public function getQueue() {
    return $this->queue;
  }
 
}

?>
