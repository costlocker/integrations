<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Api\ResponseHelper;

class ProxyClient
{
    private $client;
    private $allowedHosts;

    public function __construct(Client $c, array $allowedHosts)
    {
        $this->client = $c;
        $this->allowedHosts = $allowedHosts;
    }

    public function __invoke(Request $r)
    {
        if ($this->isUnknownHost($r->request->get('url'))) {
            return ResponseHelper::error("Host from url '{$r->request->get('url')}' is not allowed in Proxy");
        }

        $response = $this->client->request(
            strtolower($r->request->get('method')),
            $r->request->get('url'),
            [
                'http_errors' => false,
                'headers' => $r->request->get('headers'),
                'body' => $r->request->get('body'),
            ]
        );
        $json = json_decode($response->getBody());
        $body = $json;

        if ($r->request->get('isDebug')) {
            $body = [
                'headers' => $response->getHeaders(),
                'body' => $json,
            ];
        }
        return new JsonResponse($body, $response->getStatusCode());
    }

    private function isUnknownHost($url)
    {
        $host = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
        foreach ($this->allowedHosts as $allowedHost) {
            if ($allowedHost == $host) {
                return false;
            }
        }
        return true;
    }
}
