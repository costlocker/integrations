<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\CostlockerClient;

class GetUser
{
    private $session;
    private $client;

    public function __construct(SessionInterface $s, CostlockerClient $c)
    {
        $this->session = $s;
        $this->client = $c;
    }

    public function __invoke($checkCostlockerAccount = false)
    {
        $costlocker = $this->session->get('costlocker');
        if ($costlocker && $checkCostlockerAccount) {
            $response = $this->client->__invoke('/me');
            if ($response->getStatusCode() !== 200) {
                $costlocker = [];
            }
        }

        return new JsonResponse([
            'harvest' => $this->session->get('harvest')['account'] ?? null,
            'costlocker' => $costlocker['account'] ?? null,
        ]);
    }
}
