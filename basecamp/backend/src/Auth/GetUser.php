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
            'harvest' => $this->session->get('harvest')['account'] ?? null,
            'costlocker' => $this->session->get('costlocker')['account'] ?? null,
        ]);
    }

    public function getHarvestUrl()
    {
        return $this->session->get('harvest')['account']['company_url'];
    }

    public function getHarvestAuthorization()
    {
        return $this->session->get('harvest')['auth'];
    }

    public function getHarvestSubdomain()
    {
        return $this->session->get('harvest')['account']['company_subdomain'];
    }

    public function getCostlockerEmail()
    {
        return $this->session->get('costlocker')['account']['person']['email'];
    }

    public function getCostlockerCompanyId()
    {
        return $this->session->get('costlocker')['account']['company']['id'];
    }

    public function getCostlockerAccessToken()
    {
        return $this->session->get('costlocker')['accessToken']['access_token'];
    }
}
