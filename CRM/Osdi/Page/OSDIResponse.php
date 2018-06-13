<?php
use CRM_Osdi_ExtensionUtil as E;

require_once __DIR__ . '/../../../api/v3/Exporter/Export.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use Ekino\HalClien\Resource;

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
        $contact = json_decode(file_get_contents('php://input'));

        $client = new FileGetContentsHttpClient("localhost");
        $person = Resource::create($client, $contact);

        if (ActionNetworkContactImporter::validate_endpoint_data($person, NULL)) {
            // remember custom fields!
           
            $result = civicrm_api3('Contact', 'get', array(
                'email' => $properties["email_addresses"][0]["address"]
            )); 
           
            if (sizeof($result["values"]) == 0) {
                $result = civicrm_api3('Contact', 'create', array(
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'display_name' => $contact["family_name"],
                    'contact_type' => 'Individual',
                    'dupe_check' => 1,
                    'check_permission' => 1
                ));
            } else {
                $result = civicrm_api3('Contact', 'create', array(
                    'id' => $contact_id,
                    'first_name' => $contact["given_name"],
                    'last_name' => $contact["family_name"],
                    'email' => $contact["email_addresses"][0]["address"],
                    'display_name' => $contact["family_name"],
                    'contact_type' => 'Individual',
                ));
            }
        }
    }

    CRM_Utils_System::civiExit();
    parent::run();
  }

}
