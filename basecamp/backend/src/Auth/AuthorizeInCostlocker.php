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
    private $logoutUser;
    private $appUrl;

    public function __construct(
        SessionInterface $s,
        GenericProvider $p,
        PersistCostlockerUser $db,
        LoggerInterface $l,
        LogoutUser $u,
        $appUrl
    ) {
        $this->session = $s;
        $this->provider = $p;
        $this->persistUser = $db;
        $this->logger = $l;
        $this->logoutUser = $u;
        $this->appUrl = $appUrl;
    }

    public function __invoke(Request $r)
    {
        if (!getenv('CL_CLIENT_ID')) {
            return $this->sendError('Disabled...');
        }
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
                $allowedRoles = ['OWNER', 'ADMIN', 'MANAGER'];
                if (!in_array($costockerRole, $allowedRoles)) {
                    $rolesCsv = implode(' or ', $allowedRoles);
                    return $this->sendError("Only {$rolesCsv} can import project, you are {$costockerRole}");
                }
                $this->session->remove('costlockerLogin');
                list($costlockerId, $basecampId) = $this->persistUser->__invoke($costlockerUser, $accessToken);
                $this->session->set('costlocker', ['userId' => $costlockerId]);
                $this->session->set('basecamp', ['userId' => $basecampId]);
                $this->session->set('csrfToken', sha1($r->query->get('state')));
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
        $this->logoutUser->__invoke();
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
