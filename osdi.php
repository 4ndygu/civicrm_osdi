<?php


require_once 'osdi.civix.php';
use CRM_Osdi_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function osdi_civicrm_config(&$config) {
  _osdi_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function osdi_civicrm_xmlMenu(&$files) {
  _osdi_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function osdi_civicrm_install() {
  _osdi_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function osdi_civicrm_postInstall() {
  install_groupid();
  //install_matching();

  _osdi_civix_civicrm_postInstall();
}

function install_groupid() {
  $id = -1;

  try {
      $result = civicrm_api3('CustomGroup', 'create', array(
          'title' => "osditags",
          'extends' => "Contact",
          'is_multiple' => 1,
          'max_multiple' => 0,
      ));
  } catch (CiviCRM_API3_Exception $e) {
      if ($e->getErrorCode() === "already exists") {
          $result = civicrm_api3('CustomGroup', 'get', array(
              'title' => "osditags",
              'sequential' => 1
          ));
      } 

      $id = $result["id"];
      Civi::settings()->set('OSDIGROUPID', 'osditags');
  }

  // add the existing ID to this group
  $key = "CIVI_ID_" . sha1(CRM_Utils_System::url("civicrm", NULL, TRUE, NULL, FALSE, TRUE));
  try {
    $fieldresult = civicrm_api3('CustomField', 'create', array(
      'custom_group_id' => $id,
      'label' => $key,
      'data_type' => 'String',
      'html_type' => "Text",
    ));
  } catch (CiviCRM_API3_Exception $e) {
    if ($e->getErrorCode() !== "already exists") throw $e;
  }

  $id = $result["id"];
  Civi::settings()->set('OSDIGROUPID', 'osditags');
}

function install_matching() {
  // set ID for later
  $id = -1;
  $id2 = -1;

  // generate mapping (get or set)
  $firstitem = civicrm_api3('Mapping', 'get', array(
      'name' => "osdi_contact",
  ));

  if (sizeof($firstitem["values"]) == 0) {
      $mappingresult = civicrm_api3('Mapping', 'create', array(
          'name' => "osdi_contact",
          'description' => "field matching rules for OSDI Contacts",
          'mapping_type_id' => "Import Contact",
      ));
      $id = $mappingresult["id"];
  } else {
      $id = $firstitem["id"];
  }

  // do same for remote fields
  $seconditem = civicrm_api3('Mapping', 'get', array(
      'name' => "osdi_contact_remote",
  ));
  
  if (sizeof($seconditem["values"]) == 0) {
      $remoteresult = civicrm_api3('Mapping', 'create', array(
          'name' => "osdi_contact_remote",
          'description' => "field matching rules for OSDI Contacts (remote)",
          'mapping_type_id' => "Import Contact",
      ));
      $id2 = $remoteresult["id"];
  } else {
      $id2 = $seconditem["id"];
  }

  Civi::settings()->set('OSDIContactMatchingRule', 'osdi_contact');
  Civi::settings()->set('OSDIContactMatchingRuleRemote', 'osdi_contact_remote');

  $result = civicrm_api3('Contact', 'getfields', [
      'api_action' => "",

  ]);
  $fields = array_keys($result["values"]);

  // generate mapping for each fieldS
  $default_mappings = include('config.php');
  foreach ($fields as $field) {
      $first = $field;
      $second = $field;
      // check if in default
      if (isset($default_mappings[$first])) {
          $second = $default_mappings[$first];
      }

      if (substr($field, 0, 6) == "custom") {
          // gert the field name
          $result = civicrm_api3('CustomField', 'get', array(
              'sequential' => 1,
              'id' => substr($field, 7)
          ));

          $second = "custom_fields|" . $result["values"][0]["name"];
      }

      // shunt the forward direction
      try {
        $result = civicrm_api3('MappingField', 'create', [
          'mapping_id' => $id,
          'name' => $first,
          'value' => $second,
          'column_number'=> 1
        ]);
      } catch (CiviCRM_API3_Exception $e) {
        if ($e->getErrorCode() !== "already exists") throw $e;
      }

      // shunt the backward direction
      try {
        $result = civicrm_api3('MappingField', 'create', [
            'mapping_id' => $id2,
            'name' => $second,
            'value' => $first,
            'column_number'=> 1
        ]);
      } catch (CiviCRM_API3_Exception $e) {
        if ($e->getErrorCode() !== "already exists") throw $e;
      }
  }
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function osdi_civicrm_uninstall() {
  _osdi_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function osdi_civicrm_enable() {
  install_groupid();
  install_matching();

  _osdi_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function osdi_civicrm_disable() {
  _osdi_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function osdi_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _osdi_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function osdi_civicrm_managed(&$entities) {
  _osdi_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function osdi_civicrm_caseTypes(&$caseTypes) {
  _osdi_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function osdi_civicrm_angularModules(&$angularModules) {
  _osdi_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function osdi_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _osdi_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function osdi_civicrm_entityTypes(&$entityTypes) {
  _osdi_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 */
function osdi_civicrm_preProcess($formName, &$form) {

} 

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function osdi_civicrm_navigationMenu(&$menu) {
  _osdi_civix_insert_navigation_menu($menu, 'Contacts', array(
    'label' => E::ts('Import via OSDI'),
    'name' => 'Import via OSDI',
    'url' => 'civicrm/osdi/config',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _osdi_civix_navigationMenu($menu);
} 

/**
 * Implementation of hook_civicrm_permission
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_permission
 */
function osdi_civicrm_permission(&$permissions) {
  //Until the Joomla/Civi integration is fixed, don't declare new perms
  // for Joomla installs
  if (CRM_Core_Config::singleton()->userPermissionClass->isModulePermissionSupported()) {
    $permissions = array_merge($permissions, CRM_Osdi_Permission::getOsdiPermissions());
  }
}
