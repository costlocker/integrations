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
            'fakturoid' => null,
            'costlocker' => $this->session->get('costlocker')['account'] ?? null,
            'csrfToken' => $this->session->get('csrfToken'),
        ]);
    }

    public function getCostlockerAccessToken()
    {
        return $this->session->get('costlocker')['accessToken']['access_token'];
    }
}
