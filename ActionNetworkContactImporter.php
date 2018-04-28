<?php

require_once("AbstractContactImporter.php");

class ActionNetworkContactImporter extends AbstractContactImporter
{

    private $client;
    private $explorer;
    private $adapter;

    // constructor
    public function __construct($endpoint, $schema, $apikey) {
        self.endpoint = $endpoint;
        self.schema = $schema;
        self.apikey = $apikey;

        // demo api key is c173bee8d3238bb5bfc4dd014f207feb.
        // TODO: throw $apikey into some config file.

        self.client = new \GuzzleHttp\Client();
        self.explorer = new \HalExplorer\Explorer();
        self.adapter = new \HalExplorer\ClientAdapters\Adapter();

        self.adapter->setClient($client);
        self.explorer->setAdapter($adapter)->setBaseUrl(self.endpoint);

        // override default values for the headers
        self.explorer->setDefaults(function($original){
            $original["headers"]["Accept"] = "application/hal+json, application/json";
            $original["headers"]["OSDI-API-Token"] = self.apikey;

            return $original;
        });
    }

    public function pull_endpoint_data() {
        
    }

    public function validate_endpoint_data($data) {
    }

    public function format_data($data) {
    }

    public function store_data($data) {
    }
}
?>

