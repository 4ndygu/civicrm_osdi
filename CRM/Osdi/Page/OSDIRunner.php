<?php

use CRM_Osdi_ExtensionUtil as E;

/**
 *
 */
class CRM_Osdi_Page_OSDIRunner extends CRM_Core_Page {

  /**
   *
   */
  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml.
    CRM_Utils_System::setTitle(E::ts('OSDIRunner'));

    // Retrieve the queue.
    $queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
    $runner = new CRM_Queue_Runner(array(
    // Title fo the queue.
      'title' => ts('OSDI Queue Page runner'),
    // The queue object.
      'queue' => $queue,
    // Abort upon error and keep task in queue.
      'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
    // Method which is called as soon as the queue is finished.
      'onEnd' => array('CRM_OSDIQueue_Runner', 'onEnd'),
    // Go to page after all tasks are finished.
      'onEndUrl' => CRM_Utils_System::url('civicrm/xxxx', 'reset=0'),
    ));

    // Does not return.
    $runner->runAllViaWeb();

    parent::run();
  }

  /**
   *
   */
  public static function onEnd(CRM_Queue_TaskContext $ctx) {
    // Set a status message for the user.
    CRM_Core_Session::setStatus('All tasks in queue are executes', 'Queue', 'success');
  }

}
