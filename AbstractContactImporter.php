<?php
abstract class AbstractContactImporter
{

    // endpoint
    private $endpoint;
    private $schema;
    private $apikey;
    private $client;
   
    // for raw http requests
    private $raw_client;

    abstract public function pull_endpoint_data($identifier);
    abstract public function batch_pull_endpoint_data($identifiers);
    abstract public function validate_endpoint_data($data);
    abstract public function format_data($data);
    abstract public function store_data($data);
}
?>

