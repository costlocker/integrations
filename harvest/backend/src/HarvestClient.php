<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;

class HarvestClient
{
    private $client;
    private $domain;
    private $authHeader;

    public function __construct(Client $client, $domain, $authHeader)
    {
        $this->client = $client;
        $this->domain = $domain;
        $this->authHeader = $authHeader;
    }

    public function __invoke($path, $returnStatusCode = false)
    {
        $response = $this->client->get("{$this->domain}{$path}", [
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
