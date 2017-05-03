<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;

class HarvestClient
{
    private $domain;
    private $authHeader;

    public function __construct($domain, $authHeader)
    {
        $this->domain = $domain;
        $this->authHeader = $authHeader;
    }

    public function __invoke($path, $returnStatusCode = false)
    {
        $client = new Client();
        $response = $client->get("{$this->domain}{$path}", [
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->authHeader,
            ],
        ]);
        $json = json_decode($response->getBody(), true);
        return $returnStatusCode ? [$response->getStatusCode(), $json] : $json;
    }
}
