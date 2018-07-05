<?php
use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../api/v3/Exporter/Export.php';
require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Ekino\HalClient\Resource;
use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;

if (!function_exists('getallheaders')) {
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

class CRM_Osdi_Page_Webhook extends CRM_Core_Page {

  public function run() {

    // Check CMS's permission for (presumably) anonymous users.
    if (CRM_Core_Config::singleton()->userPermissionClass->isModulePermissionSupported() && !CRM_Osdi_Permission::check('allow webhook posts')) {
      throw new RuntimeException("Missing allow webhook posts permission.", 500);
    }

    // check key and header in values
    $headers = getallheaders();

    $apikey = isset($headers["Osdi-Api-Token"]) ? $headers["Osdi-Api-Token"] : null;
    if ($apikey == NULL) {
        $apikey = isset($headers["OSDI-API-Token"]) ? $headers["OSDI-API-Token"] : null;
    }

    $object = isset($headers["Object"]) ? $headers["Object"] : null;
    // Check CMS's permission for (presumably) anonymous users.
    if ($apikey != Civi::settings()->get("security_key")) {
      throw new RuntimeException("Missing our incorrect apikey.", 500);
    }

	if ($object == NULL) {
		print "must set 'object' parameter in get or post";
		CRM_Utils_System::civiExit();
		//parent::run();
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
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contact = json_decode(file_get_contents('php://input'), True);

        if ($contact == NULL) {
	        header('Content-Type:application/hal+json', TRUE, 500);
            print "post was null";

            CRM_Utils_System::civiExit();
            //parent::run();

            return;
        }

	    $client = new FileGetContentsHttpClient("https://www.google.com");
	    $contactarray = $contact["person"];
	    $person = Resource::create($client, $contactarray);
	    //var_dump($contactarray); 

        if (ActionNetworkContactImporter::validate_endpoint_data($person, NULL)) {
            // remember custom fields!
            $result = civicrm_api3('Contact', 'get', array(
                'email' => $contactarray["email_addresses"][0]["address"]
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
                    'sequential' => 1
                ));
            } else {
                $result = civicrm_api3('Contact', 'create', array(
                    'id' => $result["id"],
                    'first_name' => $contactarray["given_name"],
                    'last_name' => $contactarray["family_name"],
                    'email' => $contactarray["email_addresses"][0]["address"],
                    'display_name' => $contactarray["family_name"],
                    'contact_type' => 'Individual',
                    'sequential' => 1
                ));
	    }
            print json_encode(convertContactOSDI($result["values"][0], array()), JSON_PRETTY_PRINT);
        }
    }

    CRM_Utils_System::civiExit();
    //parent::run();
  }

}
