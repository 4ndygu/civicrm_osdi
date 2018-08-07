<?php

/**
 * @file
 */

/**
 * OSDIQueue.Run API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   description of fields supported by this API call.
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_o_s_d_i_queue_Run_spec(&$spec) {
  // $spec['magicword']['api.required'] = 1;.
}

/**
 * OSDIQueue.Run API.
 *
 * @param array $params
 *
 * @return array API result descriptor
 *
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 *
 * @throws API_Exception
 */
function civicrm_api3_o_s_d_i_queue_Run($params) {
  $returnValues = array();

  // Retrieve the queue.
  $queue = CRM_OSDIQueue_Helper::singleton()->getQueue();

  $runner = new CRM_Queue_Runner(array(
  // Title fo the queue.
    'title' => ts('OSDI Queue runner'),
  // The queue object.
    'queue' => $queue,
  // Continue on error otherwise the queue will hang.
    'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
  ));

  $result = $runner->runAll();
  $returnValues["result"] = $result;

  return civicrm_api3_create_success($returnValues, $params, 'OSDIQueue', 'Run');
}
