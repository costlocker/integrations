<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Costlocker\Integrations\Auth\GetUser;

class HarvestClient
{
    private $client;
    private $getUser;

    public function __construct(Client $c, GetUser $u)
    {
        $this->client = $c;
        $this->getUser = $u;
    }

    public function __invoke($path)
    {
        $response = $this->getResponse($path);
        return json_decode($response->getBody(), true);
    }

    public function getResponse($path)
    {
        return $this->client->get("{$this->getUser->getHarvestUrl()}{$path}", [
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->getUser->getHarvestAuthorization(),
            ],
        ]);
    }
}
