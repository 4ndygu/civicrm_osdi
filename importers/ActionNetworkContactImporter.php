<?php

require_once("AbstractContactImporter.php");
require_once("ResourceStruct.php");
require_once("PeopleStruct.php");

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

    public function pull_endpoint_data($filter = NULL, $rule = NULL, $group = -1) {
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

        $final_data = new ResourceStruct($resource_root, $rule, $filter, $group);

		$_SESSION["extractors"][] = serialize($final_data);
		
		return $counter;
    }

    public function update_endpoint_data($date, $filter = NULL, $rule = NULL, $group = -1) {
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
        $final_data = new ResourceStruct($data, $rule, $filter, $group);

		// shunt the root into the queue
		if (!isset($_SESSION["extractors"])) {
			$_SESSION["extractors"] = array();
		} 

		$_SESSION["extractors"][] = serialize($final_data);

		$serialized_item = serialize($final_data);
		return $serialized_item;
    }

    public static function validate_endpoint_data($person, $filter = NULL) {
		$properties = $person->getProperties();
		$checks = array("family_name", "given_name", "email_addresses");
		foreach ($checks as $check) {
			if (!array_key_exists($check, $properties)) {
				return False;
			}
		}

        $filters = preg_split('/\s+/', $filter, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($filters as $single_filter) {
			if (!array_key_exists($single_filter, $properties)) {
				return False;
			} else if (ctype_space($properties[$single_filter])) {
				return False;
            }
		}

		return True;
    }

    public static function add_task_with_page($page, $rule = NULL, $groupid = -1) {
		// this queue is created as a temp copy to preserve the static function
		$tempqueue = CRM_OSDIQueue_Helper::singleton()->getQueue();

        $peoplestruct = new PeopleStruct($page->getProperties(), $rule, $groupid);

        $task = new CRM_Queue_Task(
            array('CRM_OSDIQueue_Tasks', 'AddContact'), //call back method
            array(serialize($peoplestruct))
        );

        //now add this task to the queue
        $tempqueue->createItem($task);
    }

    public static function merge_task_with_page($rule = NULL) {
        if ($rule == NULL) return;

		// this queue is created as a temp copy to preserve the static function
		$tempqueue = CRM_OSDIQueue_Helper::singleton()->getQueue();

        $peoplestruct = new PeopleStruct(array(), $rule);

        $task = new CRM_Queue_Task(
            array('CRM_OSDIQueue_Tasks', 'MergeContacts'), //call back method
            array(serialize($peoplestruct))
        );

        //now add this task to the queue
        $tempqueue->createItem($task);
    }
}

?>

