<?php

/**
 * @file
 */

require_once __DIR__ . '/../../../api/v3/Exporter/Export.php';
require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Ekino\HalClient\Resource;
use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;

if (!function_exists('getallheaders')) {

  /**
   *
   */
  function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }
    return $headers;
  }

}
/**
 *
 */
class CRM_Osdi_Page_Webhook extends CRM_Core_Page {

  /**
   *
   */
  public function run() {

    // Check CMS's permission for (presumably) anonymous users.
    if (CRM_Core_Config::singleton()->userPermissionClass->isModulePermissionSupported() && !CRM_Osdi_Permission::check('allow webhook posts')) {
      throw new RuntimeException("Missing allow webhook posts permission.", 500);
    }

    // Check key and header in values.
    $headers = getallheaders();

    $apikey = isset($headers["Osdi-Api-Token"]) ? $headers["Osdi-Api-Token"] : NULL;
    if ($apikey == NULL) {
      $apikey = isset($headers["OSDI-API-Token"]) ? $headers["OSDI-API-Token"] : NULL;
    }

    $object = isset($headers["Object"]) ? $headers["Object"] : NULL;
    // Check CMS's permission for (presumably) anonymous users.
    if ($apikey != Civi::settings()->get("security_key")) {
      throw new RuntimeException("Missing our incorrect apikey.", 500);
    }

    if ($object == NULL) {
      print "must set 'object' parameter in get or post";
      CRM_Utils_System::civiExit();
      // parent::run();
      return;
    }

    $params = array();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $params["object"] = $object;

      $optionals = array("apikey", "sitekey", "page", "limit", "id", "filter");
      foreach ($optionals as $optional) {
        if (isset($_GET[$optional])) {
          $params[$optional] = $_GET[$optional];
        }
      }

      $result = civicrm_api3('Exporter', 'export', $params);

      header('Content-Type:application/hal+json', TRUE, 200);
      print json_encode($result["values"]);
    }
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $contact = json_decode(file_get_contents('php://input'), TRUE);

      if ($contact == NULL) {
        header('Content-Type:application/hal+json', TRUE, 500);
        print "post was null";

        CRM_Utils_System::civiExit();
        // parent::run();
        return;
      }

      //dummy $client just so i can use the ekino hal library
      $client = new FileGetContentsHttpClient("https://www.google.com");
      $contactarray = $contact["person"];
      $person = Resource::create($client, $contactarray);
      // var_dump($contactarray);
      if (ActionNetworkContactImporter::validate_endpoint_data($person, NULL)) {
        // Remember custom fields!
        $result = civicrm_api3('Contact', 'get', array(
          'email' => $contactarray["email_addresses"][0]["address"],
        ));

        if (sizeof($result["values"]) == 0) {
          $result = civicrm_api3('Contact', 'create', array(
            'first_name' => $contactarray["given_name"],
            'last_name' => $contactarray["family_name"],
            'email' => $contactarray["email_addresses"][0]["address"],
            'display_name' => $contactarray["family_name"],
            'contact_type' => 'Individual',
            'dupe_check' => 1,
            'check_permission' => 1,
            'sequential' => 1,
          ));
        }
        else {
          // load all relevant parameters
          $params = array(
            'id' => $result["id"],
            'first_name' => $contactarray["given_name"],
            'last_name' => $contactarray["family_name"],
            'email' => $contactarray["email_addresses"][0]["address"],
            'preferred_language' => isset($contactarray["preferred_language"]) ? $contactarray["preferred_language"] : "",
            'prefix_id' => isset($contactarray["honorific_prefix"]) ? $contactarray["honorific_prefix"] : "",
            'suffix_id' => isset($contactarray["honorific_suffix"]) ? $contactarray["honorific_suffix"] : "",
            'current_employer' => isset($contactarray["employer"]) ? $contactarray["employer"] : "",
            'display_name' => $contactarray["family_name"],
            'contact_type' => 'Individual',
            'sequential' => 1,
          );

          if (isset($contactarray["postal_addresses"])) {
            if (sizeof($contactarray["postal_addresses"]) != 0) {
              $params["city"] = isset($contactarray["postal_addresses"][0]["locality"])
                ? $contactarray["postal_addresses"][0]["locality"] : "";
              $params["state_province_name"] = isset($contactarray["postal_addresses"][0]["region"])
                ? $contactarray["postal_addresses"][0]["region"] : "";
              $params["country"] = isset($contactarray["postal_addresses"][0]["country"])
                ? $contactarray["postal_addresses"][0]["country"] : "";
              $params["postal_code"] = isset($contactarray["postal_addresses"][0]["postal_code"])
                ? $contactarray["postal_addresses"][0]["postal_code"] : "";
              if (isset($contactarray["postal_addresses"][0]["address_lines"])) {
                if (sizeof($contactarray["postal_addresses"][0]["address_lines"]) != 0) {
                  $params["street_address"] = $contactarray["postal_addresses"][0]["address_lines"][0];
                }
              }
            }
          }

          if (isset($contactarray["phone_numbers"])) {
            if (sizeof($contactarray["phone_numbers"]) != 0) {
              $params["phone"] = isset($contactarray["phone_numbers"][0]["number"])
                  ? $contactarray["phone_numbers"][0]["number"] : "";
              $params["do_not_phone"] = isset($contactarray["phone_numbers"][0]["do_not_call"])
                ? $contactarray["phone_numbers"][0]["do_not_call"] : "";
            }
          }

          if (isset($contactarray["birthdate"])) {
            $yearitems = array();
            $yearitems[] = isset($contactarray["birthdate"]["year"])
              ? $contactarray["birthdate"]["year"] : "";
            $yearitems[] = isset($contactarray["birthdate"]["day"])
              ? $contactarray["birthdate"]["day"] : "";
            $yearitems[] = isset($contactarray["birthdate"]["month"])
              ? $contactarray["birthdate"]["month"] : "";
            $params["birth_date"] = join("-", $yearitems);

            $items = explode("-", $params["birth_date"]);
            foreach ($items as $item) {
              if ($item == "") { 
                var_dump($contactarray["birthdate"]);
                unset($params["birth_date"]);
              }
            }
          }

          // load all custom fields
          if (isset($contactarray["custom_fields"])) {
            foreach ($contactarray["custom_fields"] as $custom_key => $custom_value) {
              // check if the key exists
              $keyexistsresult = civicrm_api3('CustomField', 'get', array(
                'sequential' => 1,
                'label' => $custom_key
              ));

              // if yes, set the value
              if (sizeof($keyexistsresult["values"]) != 0) {
                $custom_id = $keyexistsresult["values"][0]["id"];
                $params["custom_" . $custom_id] = $custom_value;
              }
            }
          }

          $result = civicrm_api3('Contact', 'create', $params);
        }
        print json_encode(convertContactOSDI($result["values"][0], array()), JSON_PRETTY_PRINT);
      }
    }

    CRM_Utils_System::civiExit();
    // parent::run();
  }

}
