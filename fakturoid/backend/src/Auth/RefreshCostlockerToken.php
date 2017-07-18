<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\OAuth2\Client\Provider\AbstractProvider;

class RefreshCostlockerToken
{
    private $session;
    private $provider;
    private $expirationInSeconds;

    public function __construct(SessionInterface $s, AbstractProvider $p, $expirationInSeconds)
    {
        $this->session = $s;
        $this->provider = $p;
        $this->expirationInSeconds = $expirationInSeconds;
    }

    public function __invoke()
    {
        $settings = $this->session->get('costlocker');
        $expiringToken = $settings['accessToken'];

        if ($this->isExpiring($expiringToken)) {
            $accessToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $expiringToken['refresh_token'],
            ]);
            $settings['accessToken'] = $accessToken->jsonSerialize();
            $this->session->set('costlocker', $settings);
        }

        return date('c', $settings['accessToken']['expires']);
    }

    private function isExpiring(array $expiringToken)
    {
        $expiration = $expiringToken['expires'] ?? null;
        return $expiration && ($expiration - time()) <= $this->expirationInSeconds;
    }
}
