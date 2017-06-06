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

    public function getCostlockerUserId()
    {
        return $this->session->get('costlocker')['userId'];
    }

    public function getCostlockerAccessToken()
    {
        return $this->session->get('costlocker')['accessToken']['access_token'];
    }

    public function getBasecampAccessToken()
    {
        return $this->session->get('basecamp')['accessToken']['access_token'];
    }

    public function getBasecampAccount($accountId)
    {
        foreach ($this->session->get('basecamp')['account']['accounts'] as $account) {
            if ($account['id'] == $accountId) {
                return $account;
            }
        }
        return [
            'product' => '',
            'href' => 'https://3.basecampapi.com',
        ];
    }
}
