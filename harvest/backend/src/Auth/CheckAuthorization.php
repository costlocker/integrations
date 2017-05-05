<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Api\ResponseHelper;

class CheckAuthorization
{
    private $session;
    private $client;

    public function __construct(SessionInterface $s, CostlockerClient $c)
    {
        $this->session = $s;
        $this->client = $c;
    }

    public function checkAccount($service)
    {
        if (!$this->session->get($service)) {
            return ResponseHelper::error("Unathorized in {$service}", 401);
        }
    }

    public function verifyCostlockerToken()
    {
        $costlocker = $this->session->get('costlocker');
        if ($costlocker) {
            $response = $this->client->__invoke('/me');
            if ($response->getStatusCode() !== 200) {
                $this->session->remove('costlocker');
            }
        }
    }
}
