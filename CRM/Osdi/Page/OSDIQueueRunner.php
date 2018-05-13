<?php
use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/OSDIQueueHelper.php';
require_once 'CRM/Core/Page.php';

class CRM_Osdi_Page_OSDIQueueRunner extends CRM_Core_Page {

	public function run() {
		//retrieve the queue
		$queue = OSDIQueueHelper::singleton()->getQueue();
		var_dump($queue);
		$runner = new CRM_Queue_Runner(array(
			'title' => ts('OSDI Queue Page runner'), //title fo the queue
			'queue' => $queue, //the queue object
			'errorMode' => CRM_Queue_Runner::ERROR_ABORT, //abort upon error and keep task in queue
			'onEnd' => array('CRM_Osdi_Page_OSDIQueueRunner', 'onEnd'), //method which is called as soon as the queue is finished
			'onEndUrl' => CRM_Utils_System::url('civicrm', 'reset=1'), //go to page after all tasks are finished
		));

		var_dump("doing this");
		var_dump($runner->runAll()); // does not return
	}

	static function onEnd(CRM_Queue_TaskContext $ctx) {
		//set a status message for the user
		var_dump("DONE");
		CRM_Core_Session::setStatus('All tasks in queue are executes', 'Queue', 'success');
	}

}
