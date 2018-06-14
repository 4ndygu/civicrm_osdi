<?php
use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../api/v3/Exporter/Export.php';
require_once __DIR__ . '/../../../importers/ActionNetworkContactImporter.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Ekino\HalClient\Resource;
use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;

class CRM_Osdi_Page_OSDIResponse extends CRM_Core_Page {

  public function run() {

    $params = array();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET["object"])) {
            print "must set 'object' parameter";

            CRM_Utils_System::civiExit();
            parent::run();
            return;
        }

        $params["object"] = $_GET["object"];

        $optionals = array("apikey", "sitekey", "page", "limit", "id");
        foreach ($optionals as $optional) {
            if (isset($_GET[$optional])) {
                $params[$optional] = $_GET[$optional];
            }
        }

        $result = civicrm_api3('Exporter', 'export', $params);

        header('Content-Type: application/json');
        print json_encode($result["values"], JSON_PRETTY_PRINT);
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contact = json_decode(file_get_contents('php://input'), True);

        if ($contact == NULL) {
            header('Content-Type: application/json');
            print "post was null";

            CRM_Utils_System::civiExit();
            parent::run();

            return;
        }

	    $client = new FileGetContentsHttpClient("https://www.google.com");
	    $contactarray = $contact["person"];
        $person = Resource::create($client, $contactarray);

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

            print json_encode(convertContactOSDI($result["values"][0]), JSON_PRETTY_PRINT);
        }
    }

    CRM_Utils_System::civiExit();
    parent::run();
  }

}
