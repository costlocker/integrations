<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Costlocker\Integrations\Auth\GetUser;

class CostlockerClient
{
    private $client;
    private $user;
    private $domain;

    public function __construct(Client $c, GetUser $u, $domain)
    {
        $this->client = $c;
        $this->user = $u;
        $this->domain = $domain;
    }

    public function __invoke($path, array $json = null)
    {
        return $this->client->request(
            is_array($json) ? 'post' : 'get',
            $this->getUrl($path),
            [
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->user->getCostlockerAccessToken()}",
                ],
                'json' => $json,
            ]
        );
    }

    public function getUrl($path)
    {
        if (is_int(strpos($path, 'api-public/'))) {
            return $path;
        } elseif (is_int(strpos($path, '/v1/'))) {
            return "{$this->domain}/api-public{$path}";
        } else {
            return "{$this->domain}/api-public/v2{$path}";
        }
    }
}
