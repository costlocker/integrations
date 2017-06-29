<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Database\PersistFakturoidUser;

class AuthorizeInFakturoid
{
    private $client;
    private $session;
    private $persistUser;
    private $appUrl;

    public function __construct(FakturoidClient $c, SessionInterface $s, PersistFakturoidUser $db, $appUrl)
    {
        $this->client = $c;
        $this->session = $s;
        $this->persistUser = $db;
        $this->appUrl = $appUrl;
    }

    public function __invoke(Request $r)
    {
        $slug = $r->request->get('slug');
        $authorization = 
            $this->client->overrideAuthorization($r->request->get('email'), $r->request->get('token'));

        $response = $this->client->__invoke('/user.json');

        if ($response->getStatusCode() != 200) {
            return $this->sendError('Invalid fakturoid credentials');
        }

        $user = json_decode($response->getBody(), true);
        $account = $this->getSelectedAccount($user, $slug);
        if (!$account) {
            return $this->sendError("You don't have access to '{$slug}' account");
        }

        $this->persistUser->__invoke($user, $account);
        $this->session->set('fakturoid', [
            'userId' => $user['id'],
            'accessToken' => $authorization,
        ]);
        return new RedirectResponse($this->appUrl);
    }

    private function getSelectedAccount(array $user, $slug)
    {
        foreach ($user['accounts'] as $account) {
            if ($account['slug'] == $slug) {
                return $account;
            }
        }
        return null;
    }

    private function sendError($errorMessage)
    {
        $this->session->remove('fakturoid');
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
