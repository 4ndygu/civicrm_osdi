<?php

require_once("AbstractContactImporter.php");

require __DIR__ . '/../vendor/autoload.php';

use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;
use Ekino\HalClient\EntryPoint;

use Ekino\HalClient\HttpClient\HttpClientInterface;
use Ekino\HalClient\Resource;

//TODO: this isn't right lmao
require __DIR__ . '/../../../../vendor/autoload.php';

use GuzzleHttp\Client;

//TODO: Configure my include path to allow civicrm.api.php for civicrm_api3

class ActionNetworkContactImporter extends AbstractContactImporter
{

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
    }

    public function pull_endpoint_data() {
        // create an entry point to retrieve the data
        $resource_root = $this->entrypoint->get();// return the main resource

		// retrieve a Resource object, which acts as a Pager
		$people = $resource_root->get('osdi:people');

		// a ResourceCollection implements the \Iterator and \Countable interface
		foreach ($people as $person) {
			#TODO: Throw into queue
        }

    }

    public function update_endpoint_data($date) {
        // TODO: sanitize this input later
        $query_string = "/people?filter=modified_date gt " . $date;
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

        $people = Resource::create($this->client, $data);        
        foreach ($people as $person) {
    		#TODO: Throw into queue
        }
    }

    public function validate_endpoint_data($data) {
        //TODO: validate_data
    }

    public function format_data($data) {
        //TODO: format_data
    }

    public function store_data($data) {
        $embedded = $data->getEmbedded();

        foreach ($embedded as $person) {
            try {
                $contacts = civicrm_api3('Contact', 'create', array(
                    'first_name' => $person["given_name"],
                    'last_name' => $person["last_name"]
                ));
            } catch (CiviCRM_API3_Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}

$x = new ActionNetworkContactImporter("https://actionnetwork.org/api/v2", "x", "8cfc0188d2c4616b855d9b1025ef9390");
$x->update_endpoint_data("2014-01-01");
?>

