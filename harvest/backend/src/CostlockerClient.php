<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CostlockerClient
{
    private $client;
    private $session;
    private $domain;

    public function __construct(Client $c, SessionInterface $s, $domain)
    {
        $this->client = $c;
        $this->session = $s;
        $this->domain = $domain;
    }

    public function __invoke($path, array $json = null)
    {
        $accessToken = $this->session->get('costlocker')['accessToken']['access_token'];
        return $this->client->request(
            is_array($json) ? 'post' : 'get',
            $this->getUrl("/api-public/v2{$path}"),
            [
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$accessToken}",
                ],
                'json' => $json,
            ]
        );
    }

    public function getUrl($path)
    {
        return "{$this->domain}{$path}";
    }

    public function getLoggedEmail()
    {
        return $this->session->get('costlocker')['account']['person']['email'];
    }
}
