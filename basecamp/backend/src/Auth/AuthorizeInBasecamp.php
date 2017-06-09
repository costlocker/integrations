<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Costlocker\Integrations\Database\PersistBasecampUser;
use Psr\Log\LoggerInterface;

class AuthorizeInBasecamp
{
    private $session;
    private $provider;
    private $persistUser;
    private $logger;

    public function __construct(
        SessionInterface $s,
        AbstractProvider $p,
        PersistBasecampUser $db,
        LoggerInterface $l,
        $appUrl
    ) {
        $this->session = $s;
        $this->provider = $p;
        $this->persistUser = $db;
        $this->logger = $l;
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
                    'userId' => $this->persistUser->__invoke($basecampUser, $accessToken),
                ]);
                return new RedirectResponse($this->appUrl);
            } catch (IdentityProviderException $e) {
                return $this->sendError($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->error($e);
                return $this->sendError('Internal server error');
            }
        }
    }

    private function sendError($errorMessage)
    {
        $this->session->remove('basecamp');
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
