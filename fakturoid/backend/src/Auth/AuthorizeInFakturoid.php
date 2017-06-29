<?php

namespace Costlocker\Integrations\Auth;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\Database\PersistFakturoidUser;

class AuthorizeInFakturoid
{
    private $client;
    private $session;
    private $persistUser;
    private $appUrl;

    public function __construct(Client $c, SessionInterface $s, PersistFakturoidUser $db, $appUrl)
    {
        $this->client = $c;
        $this->session = $s;
        $this->persistUser = $db;
        $this->appUrl = $appUrl;
    }

    public function __invoke(Request $r)
    {
        $slug = $r->request->get('slug');
        $authorization = base64_encode("{$r->request->get('email')}:{$r->request->get('token')}");

        $response = $this->client->request(
            'get',
            'https://app.fakturoid.cz/api/v2/user.json',
            [
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'CostlockerIntegration (development@costlocker.com)',
                    'Authorization' => "Basic {$authorization}"
                ],
            ]
        );

        if ($response->getStatusCode() != 200) {
            return $this->sendError('Invalid fakturoid credentials');
        }

        $rawUser = json_decode($response->getBody(), true);
        $user = $this->removeOtherAccounts($rawUser, $slug);
        if (!$user['accounts']) {
            return $this->sendError("You don't have access to '{$slug}' account");
        }

        $fakturoidId = $this->persistUser->__invoke($user, $slug);
        $this->session->set('fakturoid', [
            'userId' => $fakturoidId,
            'account' => $user,
            'accessToken' => $authorization,
        ]);
        return new RedirectResponse($this->appUrl);
    }

    private function removeOtherAccounts(array $user, $slug)
    {
        foreach ($user['accounts'] as $id => $account) {
            if ($account['slug'] != $slug) {
                unset($user['accounts'][$id]);
            }
        }
        return $user;
    }

    private function sendError($errorMessage)
    {
        $this->session->remove('fakturoid');
        return new RedirectResponse("{$this->appUrl}?loginError={$errorMessage}");
    }
}
