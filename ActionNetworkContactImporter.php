<?php

require_once("AbstractContactImporter.php");

class ActionNetworkContactImporter extends AbstractContactImporter
{

    // constructor
    public function __construct($endpoint, $schema, $apikey) {
        self.endpoint = $endpoint;
        self.schema = $schema;
        self.apikey = $apikey;
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

