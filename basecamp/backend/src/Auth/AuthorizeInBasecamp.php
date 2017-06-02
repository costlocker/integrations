<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use FourteenFour\BasecampAuth\Provider\Basecamp;

class AuthorizeInBasecamp
{
    private $session;
    private $provider;

    public static function buildFromEnv(SessionInterface $s)
    {
        return new self(
            $s,
            new Basecamp([
                'clientId' => getenv('BASECAMP_CLIENT_ID'),
                'clientSecret' => getenv('BASECAMP_CLIENT_SECRET'),
                'redirectUri' => getenv('BASECAMP_REDIRECT_URL'),
            ]),
            getenv('APP_FRONTED_URL')
        );
    }

    public function __construct(SessionInterface $s, AbstractProvider $p, $appUrl)
    {
        $this->session = $s;
        $this->provider = $p;
        $this->appUrl = $appUrl;
    }

    public function __invoke(Request $r)
    {
        if (!$r->query->get('code') && !$r->query->get('error')) {
            $url = $this->provider->getAuthorizationUrl();
            return new RedirectResponse($url);
        } elseif ($r->query->get('error')) {
            return $this->sendError($r->query->get('error'));
        } else {
            try {
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $r->query->get('code')
                ]);
                $resourceOwner = $this->provider->getResourceOwner($accessToken);
                $basecampUser = $resourceOwner->toArray();
                $this->session->set('basecamp', [
                    'account' => $basecampUser,
                    'accessToken' => $accessToken->jsonSerialize(),
                ]);
                return new RedirectResponse($this->appUrl);
            } catch (\Exception $e) {
                return $this->sendError($e->getMessage());
            }
        }
    }

    private function sendError($errorMessage)
    {
        $this->session->remove('basecamp');
        return new RedirectResponse("{$this->appUrl}?clBasecampError={$errorMessage}");
    }
}
