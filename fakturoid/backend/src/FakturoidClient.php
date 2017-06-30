<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Costlocker\Integrations\Auth\GetUser;
use Psr\Log\LoggerInterface;

class FakturoidClient
{
    private $client;
    private $getUser;
    private $logger;

    private $authorization;

    public function __construct(Client $c, GetUser $u, LoggerInterface $l)
    {
        $this->client = $c;
        $this->getUser = $u;
        $this->logger = $l;
    }

    public function overrideAuthorization($email, $apiToken)
    {
        $this->authorization = base64_encode("{$email}:{$apiToken}");
        return $this->authorization;
    }

    public function __invoke($path, array $json = null)
    {
        $authorization = $this->authorization ?: $this->getUser->getFakturoidAuthorization();

        $response = $this->client->request(
            is_array($json) ? 'post' : 'get',
            "https://app.fakturoid.cz/api/v2{$path}",
            [
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'CostlockerIntegration (development@costlocker.com)',
                    'Authorization' => "Basic {$authorization}"
                ],
                'json' => $json,
            ]
        );

        if ($response->getStatusCode() >= 400 && !$this->authorization) {
            $this->logger->error(
                'Fakturoid API error',
                [
                    'url' => $path,
                    'request' => $json,
                    'response' => (string) $response->getBody(),
                ]
            );
        }

        return $response;
    }
}
