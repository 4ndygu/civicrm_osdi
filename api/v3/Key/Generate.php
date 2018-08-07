<?php

/**
 * @file
 */

/**
 * Key.Generate API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec
 *   description of fields supported by this API call.
 *
 * @return void
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_key_Generate_spec(&$spec) {
}

/**
 * Key.Generate API.
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
function civicrm_api3_key_Generate($params) {
  $bytes = openssl_random_pseudo_bytes(500);
  $res = base_convert($bytes, 10, 26);
  $key = "OSDI_" . strtr($res, '0123456789', 'qrstuvwxyz');

  // Store it.
  Civi::settings()->set("security_key", $key);
  $returnValues["message"] = "KEY: " . Civi::settings()->get("security_key");
  return civicrm_api3_create_success($returnValues, $params, 'Key', 'Generate');
}
