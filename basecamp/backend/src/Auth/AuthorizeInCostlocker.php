<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Costlocker\Integrations\Database\PersistCostlockerUser;
use Psr\Log\LoggerInterface;

class AuthorizeInCostlocker
{
    private $session;
    private $provider;
    private $persistUser;
    private $logger;

    public static function buildFromEnv(SessionInterface $s, PersistCostlockerUser $p, LoggerInterface $l)
    {
        $costlockerHost = getenv('CL_HOST');
        return new self(
            $s,
            new GenericProvider([
                'clientId' => getenv('CL_CLIENT_ID'),
                'clientSecret' => getenv('CL_CLIENT_SECRET'),
                'redirectUri' => null,
                'urlAuthorize' => "{$costlockerHost}/api-public/oauth2/authorize",
                'urlAccessToken' => "{$costlockerHost}/api-public/oauth2/access_token",
                'urlResourceOwnerDetails' => "{$costlockerHost}/api-public/v2/me",
            ]),
            $p,
            $l,
            getenv('APP_FRONTED_URL')
        );
    }

    public function __construct(SessionInterface $s, GenericProvider $p, PersistCostlockerUser $db, LoggerInterface $l, $appUrl)
    {
        $this->session = $s;
        $this->provider = $p;
        $this->persistUser = $db;
        $this->logger = $l;
        $this->appUrl = $appUrl;
    }

    public function __invoke(Request $r)
    {
        if (!$r->query->get('code') && !$r->query->get('error')) {
            // getState must be called after getAuthorizationUrl
            $url = $this->provider->getAuthorizationUrl();
            $this->session->set('costlockerLogin', [
                'oauthState' => $this->provider->getState(),
                'redirectUrl' => $this->appUrl,
            ]);
            return new RedirectResponse($url);
        } elseif ($r->query->get('state') != $this->session->get('costlockerLogin')['oauthState']) {
            return $this->sendError('Invalid state');
        } elseif ($r->query->get('error')) {
            return $this->sendError($r->query->get('error'));
        } else {
            try {
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $r->query->get('code')
                ]);
                $resourceOwner = $this->provider->getResourceOwner($accessToken);
                $costlockerUser = $resourceOwner->toArray()['data'];
                $costockerRole = $costlockerUser['person']['role'];
                if (!in_array($costockerRole, ['OWNER', 'ADMIN'])) {
                    return $this->sendError("Only ADMIN or OWNER can import project, you are {$costockerRole}");
                }
                $this->session->remove('costlockerLogin');
                list($costlockerId, $basecampId) = $this->persistUser->__invoke($costlockerUser, $accessToken);
                $this->session->set('costlocker', ['userId' => $costlockerId]);
                $this->session->set('basecamp', ['userId' => $basecampId]);
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
        $this->session->remove('costlocker');
        $this->session->remove('costlockerLogin');
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
