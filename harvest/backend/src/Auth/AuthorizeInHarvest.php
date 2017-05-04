<?php

namespace Costlocker\Integrations\Auth;

use Costlocker\Integrations\HarvestClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthorizeInHarvest
{
    private $session;
    private $getUser;

    public function __construct(SessionInterface $s, GetUser $u)
    {
        $this->session = $s;
        $this->getUser = $u;
    }

    public function __invoke(Request $r)
    {
        $authHeader = 'Basic ' . base64_encode("{$r->request->get('username')}:{$r->request->get('password')}");
        $client = new HarvestClient("https://{$r->request->get('domain', 'a')}.harvestapp.com", $authHeader);
        list($statusCode, $json) = $client("/account/who_am_i", true);
        if ($statusCode != 200) {
            return new JsonResponse([], $statusCode);
        }
        $this->session->set('harvest', [
            'account' => [
                'company_name' => $json['company']['name'],
                'company_url' => $json['company']['base_uri'],
                'user_name' => "{$json['user']['first_name']} {$json['user']['last_name']}",
                'user_avatar' => $json['user']['avatar_url'],
            ],
            'auth' => $authHeader,
        ]);
        return $this->getUser->__invoke();
    }
}
