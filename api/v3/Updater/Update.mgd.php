<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array (
  0 => 
  array (
    'name' => 'Cron:Updater.Update',
    'entity' => 'Job',
    'params' => 
    array (
      'version' => 3,
      'name' => 'Call Updater.Update API',
      'description' => 'Call Updater.Update API',
      'run_frequency' => 'Daily',
      'api_entity' => 'Updater',
      'api_action' => 'Update',
      'key' => 'password',
      'parameters' => '',
    ),
  ),
);
