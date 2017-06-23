<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampAdapter;
use Costlocker\Integrations\Api\ResponseHelper;

class CheckAuthorization
{
    private $session;
    private $costlockerClient;
    private $basecampAdapter;

    public function __construct(SessionInterface $s, CostlockerClient $c, BasecampAdapter $b)
    {
        $this->session = $s;
        $this->costlockerClient = $c;
        $this->basecampAdapter = $b;
    }

    public function checkAccount($service)
    {
        if (!$this->session->get($service)) {
            return ResponseHelper::error("Unauthorized in {$service}", 401);
        }
    }

    public function checkCsrfToken($csrfToken)
    {
        $sessionToken = $this->session->get('csrfToken');
        if (!$sessionToken || $csrfToken != $sessionToken) {
            return ResponseHelper::error("Invalid CSRF token", 403);
        }
    }

    public function verifyTokens()
    {
        $this->verifyToken('costlocker', $this->costlockerClient, '__invoke', '/me');
        $this->verifyBasecampToken();
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

    private function verifyBasecampToken()
    {
        if (!$this->checkAccount('basecamp')) {
            try {
                $bcUserId = $this->session->get('basecamp')['userId'];
                $client = $this->basecampAdapter->buildClient($bcUserId);
                $client->getProjects();
            } catch (\Exception $e) {
                $this->session->remove('basecamp');
            }
        }
    }
}
