<?php

require_once "AbstractContactImporter.php";
require_once "ResourceStruct.php";
require_once "PeopleStruct.php";

require __DIR__ . '/../vendor/autoload.php';

use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;
use Ekino\HalClient\EntryPoint;

use Ekino\HalClient\Resource;

use GuzzleHttp\Client;

/**
 * TODO: Configure my include path to allow civicrm.api.php for civicrm_api3.
 */
class CiviCRMContactImporter extends AbstractContactImporter {

  private $queue;

  /**
   * Constructor.
   */
  public function __construct($endpoint, $schema, $apikey) {
    $this->endpoint = $endpoint;
    $this->schema = $schema;
    $this->apikey = $apikey;

    // Create a HttpClient to perform http request.
    $this->client = new FileGetContentsHttpClient($this->endpoint, array(
      'OSDI-API-Token' => $this->apikey,
      'Object' => 'Contact',
      'Content-Type' => "application/hal+json",
    ));

    $this->entrypoint = new EntryPoint('/osdi/webhook', $this->client);

    // Seed a client in Guzzle to craft raw queries.
    $this->raw_client = new Client();

    // Setup queue
    // $this->queue = CRM_OSDIQueue_Helper::singleton()->getQueue();
  }

  /**
   * given a URL, request the relevant object
   */
  public function request_object($full_uri) {
    $response = $this->raw_client->request('GET', $full_uri, [
     'headers' => [
       'OSDI-API-Token' => $this->apikey,
       'Object' => 'Contact',
       'Content-Type' => "application/hal+json"
      ],
    ]);
    
    // Wrap everything into a hal-client resource so nobody knows I used Guzzle.
    $response_string = $response->getBody()->getContents();
    $data = json_decode($response_string, TRUE);
    $data = Resource::create($this->client, $data);

    return $data;
  }

  /**
   *
   */
  public function update_endpoint_data($date, $filter = NULL, $rule = NULL, $group = -1, $zone = 0) {
    // TODO: sanitize this input later.
    // The actual heavy lifting is done by the request_object function, but we 
    // want any errors to throw first. 
    $query_string = "/osdi/webhook?filter=modified_date gt '" . $date . "'";
    $full_uri = $this->endpoint . $query_string;
    $response = $this->raw_client->request('GET', $full_uri, [
      'headers' => [
        'OSDI-API-Token' => $this->apikey,
        'Object' => 'Contact',
        'Content-Type' => "application/hal+json",
      ],
    ]);
    // Wrap everything into a hal-client resource so nobody knows I used Guzzle.
    $response_string = $response->getBody()->getContents();
    $data = json_decode($response_string, TRUE);
    $data = Resource::create($this->client, $data);

    $entryobject = array();
    $entryobject["endpoint"] = $full_uri;
    $headerobject = var_dump($data)["client"]["defaultHeaders"];
    $entryobject["headers"] = $headerobject;

    $final_data = new ResourceStruct($entryobject, $rule, $filter, $group, $zone, $this->apikey, $this->endpoint);

    // Shunt the root into the queue.
    $extractors = Civi::settings()->get("extractors");
    if ($extractors == NULL) {
      $extractors = array();
    }

    $extractors[] = serialize($final_data);
    Civi::settings()->set("extractors", $extractors);

    $serialized_item = serialize($final_data);
    return $serialized_item;
  }

  /**
   *
   */
  public static function is_newest_endpoint_data($data, $date, $zone) {
    $properties = $data->getProperties();

    $result = civicrm_api3('Contact', 'get', array(
      'first_name' => $properties["given_name"],
      'last_name' => $properties["family_name"],
      'email' => $properties["email_addresses"][0]["address"],
    ));
    $modified_result = civicrm_api3('Contact', 'get', array(
      'first_name' => $properties["given_name"],
      'last_name' => $properties["family_name"],
      'return' => ["modified_date"],
      'email' => $properties["email_addresses"][0]["address"],
      'sequential' => 1,
    ));

    if (sizeof($result["values"]) == 0) {
      return TRUE;
    }

    $converted_date = strtotime($modified_result["values"][0]["modified_date"]) - 3600 * $zone;

    if ($converted_date > strtotime($date)) {
      return FALSE;
    }

    // This should be return true.
    return TRUE;
  }

  /**
   *
   */
  public static function validate_endpoint_data($person, $filter = NULL) {
    $properties = $person->getProperties();
    $checks = array("family_name", "given_name", "email_addresses");
    foreach ($checks as $check) {
      if (!array_key_exists($check, $properties)) {
        return FALSE;
      }
    }

    $filters = preg_split('/\s+/', $filter, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($filters as $single_filter) {
      if (!array_key_exists($single_filter, $properties)) {
        return FALSE;
      }
      elseif (ctype_space($properties[$single_filter])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   *
   */
  public static function add_task_with_page($page, $rule = NULL, $groupid = -1, $apikey, $endpoint) {
    // This queue is created as a temp copy to preserve the static function.
    $tempqueue = CRM_OSDIQueue_Helper::singleton()->getQueue();

    $peoplestruct = new PeopleStruct($page->getProperties(), $rule, $groupid, $apikey, $endpoint);

    $task = new CRM_Queue_Task(
    // Call back method.
        array('CRM_OSDIQueue_Tasks', 'AddContact'),
        array(serialize($peoplestruct))
    );

    // Now add this task to the queue.
    $tempqueue->createItem($task);
  }

  /**
   *
   */
  public static function merge_task_with_page($rule = NULL) {
    if ($rule == NULL) {
      return;
    }

    // This queue is created as a temp copy to preserve the static function.
    $tempqueue = CRM_OSDIQueue_Helper::singleton()->getQueue();

    $peoplestruct = new PeopleStruct(array(), $rule, -1, NULL, "");

    $task = new CRM_Queue_Task(
    // Call back method.
        array('CRM_OSDIQueue_Tasks', 'MergeContacts'),
        array(serialize($peoplestruct))
    );

    // Now add this task to the queue.
    $tempqueue->createItem($task);
  }

}
