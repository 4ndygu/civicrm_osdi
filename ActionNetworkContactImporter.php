<?php

require_once("AbstractContactImporter.php");

require __DIR__ . '/vendor/autoload.php';

use Ekino\HalClient\HttpClient\FileGetContentsHttpClient;
use Ekino\HalClient\EntryPoint;

use Ekino\HalClient\HttpClient\HttpClientInterface;
use Ekino\HalClient\Resource;

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
       
        // seed a client in Guzzle to craft raw queries
        $this->raw_client = new GuzzleHttp\Client();
    }

    public function pull_endpoint_data($identifier) {
        // create an entry point to retrieve the data
        $query_string = "/people?filter=email_address eq 'testone@gmail.com'";
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
        return Resource::create($this->client, $data);        
    }

    public function batch_pull_endpoint_data($identifiers) {
        
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
?>

