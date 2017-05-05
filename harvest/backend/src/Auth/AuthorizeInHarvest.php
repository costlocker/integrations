<?php

namespace Costlocker\Integrations\Auth;

use Costlocker\Integrations\HarvestClient;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\Api\ResponseHelper;

class AuthorizeInHarvest
{
    private $client;
    private $session;
    private $getUser;

    public function __construct(Client $c, SessionInterface $s, GetUser $u)
    {
        $this->client = $c;
        $this->session = $s;
        $this->getUser = $u;
    }

    public function __invoke(Request $r)
    {
        $authHeader = 'Basic ' . base64_encode("{$r->request->get('username')}:{$r->request->get('password')}");
        $client = new HarvestClient(
            $this->client, 
            "https://{$r->request->get('domain', 'a')}.harvestapp.com",
            $authHeader
        );
        list($statusCode, $json) = $client("/account/who_am_i", true);
        if ($statusCode != 200) {
            return ResponseHelper::error('Not logged in', $statusCode);
        }
        $this->session->set('harvest', [
            'account' => [
                'company_name' => $json['company']['name'],
                'company_url' => $json['company']['base_uri'],
                'company_subdomain' => str_replace('.harvestapp.com', '', $json['company']['full_domain']),
                'user_name' => "{$json['user']['first_name']} {$json['user']['last_name']}",
                'user_avatar' => $json['user']['avatar_url'],
            ],
            'auth' => $authHeader,
        ]);
        return $this->getUser->__invoke();
    }
}
