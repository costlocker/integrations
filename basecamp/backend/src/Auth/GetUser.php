<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class GetUser
{
    private $session;

    public function __construct(SessionInterface $s)
    {
        $this->session = $s;
    }

    public function __invoke()
    {
        return new JsonResponse([
            'basecamp' => $this->session->get('basecamp')['account'] ?? null,
            'costlocker' => $this->session->get('costlocker')['account'] ?? null,
        ]);
    }

    public function getCostlockerAccessToken()
    {
        return $this->session->get('costlocker')['accessToken']['access_token'];
    }
}
