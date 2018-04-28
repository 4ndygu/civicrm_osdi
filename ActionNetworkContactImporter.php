<?php

require_once("AbstractContactImporter.php");

use Jsor\HalClient\HalClient;

class ActionNetworkContactImporter extends AbstractContactImporter
{

    private $client;

    // constructor
    public function __construct($endpoint, $schema, $apikey) {
        self.endpoint = $endpoint;
        self.schema = $schema;
        self.apikey = $apikey;

        // demo api key is c173bee8d3238bb5bfc4dd014f207feb.
        // TODO: throw $apikey into some config file.


        self.client = new HalClient(self.endpoint);
        self.client = self.client->withHeader("OSDI-API-Token", self.apikey);
        self.client = self.client->withHeader("Content-Type", "application/json");
    }

    public function pull_endpoint_data($identifier) {
        $resource = self.client->get('/people');
    }

    public function batch_pull_endpoint_data($identifiers) {
        
    }

    public function validate_endpoint_data($data) {
    }

    public function format_data($data) {
    }

    public function store_data($data) {
    }
}
?>

