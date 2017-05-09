<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\OAuth2\Client\Provider\GenericProvider;

class AuthorizeInHarvest
{
    private $session;
    private $provider;

    public static function buildFromEnv(SessionInterface $s)
    {
        return new self(
            $s,
            Provider\HarvestOAuthProvider::buildFromEnv(),
            getenv('APP_FRONTED_URL')
        );
    }

    public function __construct(SessionInterface $s, GenericProvider $p, $appUrl)
    {
        $this->session = $s;
        $this->provider = $p;
        $this->appUrl = $appUrl;
    }

    public function __invoke(Request $r)
    {
        if (!$r->query->get('code') && !$r->query->get('error')) {
            // getState must be called after getAuthorizationUrl
            $url = $this->provider->getAuthorizationUrl();
            $this->session->set('harvestLogin', [
                'oauthState' => $this->provider->getState(),
                'redirectUrl' => $this->appUrl,
            ]);
            return new RedirectResponse($url);
        } elseif ($r->query->get('state') != $this->session->get('harvestLogin')['oauthState']) {
            return $this->sendError('Invalid state');
        } elseif ($r->query->get('error')) {
            return $this->sendError($r->query->get('error'));
        } else {
            try {
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $r->query->get('code')
                ]);
                $json = $this->provider->getResourceOwner($accessToken)->toArray();
                $this->session->remove('harvestLogin');
                $this->session->set('harvest', [
                    'account' => [
                        'company_name' => $json['company']['name'],
                        'company_url' => $json['company']['base_uri'],
                        'company_subdomain' => str_replace('.harvestapp.com', '', $json['company']['full_domain']),
                        'user_name' => "{$json['user']['first_name']} {$json['user']['last_name']}",
                        'user_avatar' => $json['user']['avatar_url'],
                    ],
                    'auth' => 'Bearer ' . $accessToken->jsonSerialize()['access_token'],
                ]);
                return new RedirectResponse($this->appUrl);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage());
            }
        }
    }

    private function sendError($errorMessage)
    {
        $this->session->remove('harvest');
        $this->session->remove('harvestLogin');
        return new RedirectResponse("{$this->appUrl}?harvestLoginError={$errorMessage}");
    }
}
