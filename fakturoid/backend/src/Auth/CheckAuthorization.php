<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Api\ResponseHelper;
use GuzzleHttp\Psr7\Response;

class CheckAuthorization
{
    private $session;
    private $costlockerClient;
    private $fakturoidClient;

    public function __construct(SessionInterface $s, CostlockerClient $c, FakturoidClient $f)
    {
        $this->session = $s;
        $this->costlockerClient = $c;
        $this->fakturoidClient = $f;
    }

    public function checkAccount($service)
    {
        if (!$this->session->get($service)) {
            return ResponseHelper::error("Unauthorized in {$service}", 401);
        }
    }

    public function verifyTokens()
    {
        $isAddonDisabled = $this->verifyCostlockerToken();
        $this->verifyFakturoidToken();
        return $isAddonDisabled;
    }

    private function verifyCostlockerToken()
    {
        if (!$this->checkAccount('costlocker')) {
            $response = $this->costlockerClient->__invoke('/me');
            if ($response->getStatusCode() !== 200) {
                $this->session->remove('costlocker');
            } else {
                return !in_array(
                    'fakturoid',
                    json_decode((string) $response->getBody(), true)['data']['company']['addons'] ?? []
                );
            }
        }
        return false;
    }

    private function verifyFakturoidToken()
    {
        if (!$this->checkAccount('fakturoid')) {
            $response = $this->fakturoidClient->__invoke('/user.json');
            if ($response->getStatusCode() != 200 || $this->isLoggedAsDifferentUser($response)) {
                $this->session->remove('fakturoid');
            }
        }
    }

    private function isLoggedAsDifferentUser(Response $r)
    {
        $json = json_decode($r->getBody(), true);
        return $json['id'] != $this->session->get('fakturoid')['userId'];
    }
}
