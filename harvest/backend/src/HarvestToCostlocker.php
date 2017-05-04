<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HarvestToCostlocker
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

    public function __invoke(Request $r)
    {
        $projectResponse = $this->call("/projects/", []);
        if ($projectResponse->getStatusCode() != 200) {
            return new JsonResponse([], 400);
        }
        $timeentriesResponse = $this->call("/timeentries/", []);
        $projectId = 1;
        return new JsonResponse([
            'projectUrl' => "{$this->domain}/projects/detail/{$projectId}/overview"
        ]);
    }

    private function call($path, array $json)
    {
        return $this->client->post("{$this->domain}/api-public/v2{$path}", [
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $this->session->get('costlocker')['accessToken']['access_token'],
            ],
            'json' => $json,
        ]);
    }
}
