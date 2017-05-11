<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\HarvestClient;
use Costlocker\Integrations\Api\ResponseHelper;

class CheckAuthorization
{
    private $session;
    private $costlockerClient;
    private $harvestClient;

    public function __construct(SessionInterface $s, CostlockerClient $c, HarvestClient $h)
    {
        $this->session = $s;
        $this->costlockerClient = $c;
        $this->harvestClient = $h;
    }

    public function checkAccount($service)
    {
        if (!$this->session->get($service)) {
            return ResponseHelper::error("Unauthorized in {$service}", 401);
        }
    }

    public function verifyTokens()
    {
        $this->verifyToken('costlocker', $this->costlockerClient, '__invoke', '/me');
        $this->verifyToken('harvest', $this->harvestClient, 'getResponse', '/account/who_am_i');
    }

    private function verifyToken($service, $client, $method, $endpoint)
    {
        if (!$this->checkAccount($service)) {
            $response = $client->{$method}($endpoint);
            if ($response->getStatusCode() !== 200) {
                $this->session->remove($service);
            }
        }
    }
}
