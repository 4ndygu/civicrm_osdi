<?php

require_once("AbstractContactImporter.php");

require __DIR__ . '/../vendor/autoload.php';

use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;
use Ekino\HalClient\EntryPoint;

use Ekino\HalClient\HttpClient\HttpClientInterface;
use Ekino\HalClient\Resource;

use GuzzleHttp\Client;

//TODO: Configure my include path to allow civicrm.api.php for civicrm_api3

class ActionNetworkContactImporter extends AbstractContactImporter
{

    private $queue;

    // constructor
    public function __construct($endpoint, $schema, $apikey) {
        $this->endpoint = $endpoint;
        $this->schema = $schema;
        $this->apikey = $apikey;

        // create a HttpClient to perform http request
        $this->client = new FileGetContentsHttpClient($this->endpoint, array(
            'OSDI-API-Token' => $this->apikey,
            'Content-Type' => "application/json"
        ));

        $this->entrypoint = new EntryPoint('/people', $this->client);
       
        // seed a client in Guzzle to craft raw queries
        $this->raw_client = new GuzzleHttp\Client();

        // setup queue
        $this->queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
    }

    public function pull_endpoint_data() {
		$counter = 0;

        // create an entry point to retrieve the data
        $resource_root = $this->entrypoint->get();// return the main resource

		if ($resource_root->get("osdi:people") == NULL) {
			$counter = 1;
			return $counter;
		}

		// shunt the root into the queue
		if (!isset($_SESSION["extractors"])) {
			$_SESSION["extractors"] = array();
		} 

		$_SESSION["extractors"][] = serialize($resource_root);
		
		return $counter;
    }

    public function update_endpoint_data($date) {
        // TODO: sanitize this input later
        $query_string = "/people?filter=modified_date gt '" . $date . "'";
        $full_uri = $this->endpoint . $query_string;

        $response = $this->raw_client->request('GET', $full_uri, [
            'headers' => [
                'OSDI-API-Token' => $this->apikey,
                'Content-Type' => "application/json"
            ]
        ]);
    
        // wrap everything into a hal-client resource so nobody knows I used Guzzle
        $response_string = $response->getBody()->getContents();
        $data = json_decode($response_string, true);

		$data = Resource::create($this->client, $data);        

		// shunt the root into the queue
		if (!isset($_SESSION["extractors"])) {
			$_SESSION["extractors"] = array();
		} 

		$_SESSION["extractors"][] = serialize($resource_root);

		$counter = 0;
		return $counter;
    }

    public static function validate_endpoint_data($person) {
		$properties = $person->getProperties();
		$checks = array("family_name", "given_name", "email_addresses");
		foreach ($checks as $check) {
			if (!array_key_exists($check, $properties)) {
				return False;
			}
		}
		return True;
    }

    public static function add_task_with_page($page) {
		// this queue is created as a temp copy to preserve the static function
		$tempqueue = CRM_OSDIQueue_Helper::singleton()->getQueue();

        $task = new CRM_Queue_Task(
            array('CRM_OSDIQueue_Tasks', 'AddContact'), //call back method
            array($page->getProperties())
        );

        //now add this task to the queue
        $tempqueue->createItem($task);
    }
}
?>

