<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Costlocker\Integrations\Auth\GetUser;

class FakturoidClient
{
    private $client;
    private $getUser;
    private $authorization;

    public function __construct(Client $c, GetUser $u)
    {
        $this->client = $c;
        $this->getUser = $u;
    }

    public function overrideAuthorization($email, $apiToken)
    {
        $this->authorization = base64_encode("{$email}:{$apiToken}");
        return $this->authorization;
    }

    public function __invoke($path)
    {
        $authorization = $this->authorization ?: $this->getUser->getFakturoidAuthorization();

        return $this->client->request(
            'get',
            "https://app.fakturoid.cz/api/v2{$path}",
            [
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'CostlockerIntegration (development@costlocker.com)',
                    'Authorization' => "Basic {$authorization}"
                ],
            ]
        );
    }
}
