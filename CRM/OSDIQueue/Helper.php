<?php

/**
 * This is a helper class for the queue functionality.
 * It is a singleton class because it will hold the queue object for our extension
 *
 *
 */

/*foreach (glob(__DIR__ . '/../../../../CRM/Core/*.php') as $filename)
{
    require_once $filename;
}*/

/*set_include_path(get_include_path() . PATH_SEPARATOR . "/home/student/buildkit/build/my-drupal-civi47/sites/all/modules/civicrm");
require_once __DIR__ . '/../../../../CRM/Core/DAO.php';
foreach (glob(__DIR__ . '/../../../../CRM/Queue/*.php') as $filename)
{
    require_once $filename;
}*/
//eval(`cv php:boot`);

class CRM_OSDIQueue_Helper
{
    const QUEUE_NAME = 'com.civicrm.osdiqueue';
    private $queue;
    static $singleton;

    private function __construct()
    {
        $this->queue = CRM_Queue_Service::singleton()->create(array(
            'type' => 'Sql',
            'name' => self::QUEUE_NAME,
            'reset' => false, //do not flush queue upon creation
        ));
    }

    public static function singleton() {
        if (!self::$singleton) self::$singleton = new CRM_OSDIQueue_Helper();
        return self::$singleton;
    }

    public function getQueue()
    {
        return $this->queue;
    }
}

?>
