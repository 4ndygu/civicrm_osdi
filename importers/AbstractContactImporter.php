<?php
abstract class AbstractContactImporter
{

    // endpoint
    private $endpoint;
    private $schema;
    private $apikey;
    private $client;
    private $entrypoint;
   
    // for raw http requests
    private $raw_client;

    abstract public function pull_endpoint_data();
    abstract public function update_endpoint_data($date);
    abstract public function validate_endpoint_data($data);
    abstract public function format_data($data);
    abstract public function store_data($data);
}
?>

