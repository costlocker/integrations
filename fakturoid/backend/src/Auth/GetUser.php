<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\FakturoidUser;
use Costlocker\Integrations\Database\Database;

class GetUser
{
    private $session;
    private $database;

    private $costlockerUser;

    public function __construct(SessionInterface $s, Database $db)
    {
        $this->session = $s;
        $this->database = $db;
    }

    public function __invoke()
    {
        $clUser = $this->getCostlockerUser();
        return new JsonResponse([
            'costlocker' => $clUser->data,
            'fakturoid' => $clUser->fakturoidUser ? $this->transformFakturoidUser($clUser->fakturoidUser): null,
            'isLoggedInFakturoid' => $this->session->get('fakturoid') && $clUser->fakturoidUser ? true : false,
            'csrfToken' => $this->session->get('csrfToken'),
        ]);
    }

    public function getFakturoidAuthorization()
    {
        return $this->session->get('fakturoid')['accessToken'];
    }

    public function getCostlockerAccessToken()
    {
        return $this->session->get('costlocker')['accessToken']['access_token'];
    }

    public function getFakturoidAccount()
    {
        return $this->getCostlockerUser()->fakturoidUser->fakturoidAccount;
    }

    public function getCostlockerUser()
    {
        if (!$this->costlockerUser) {
            $id = $this->session->get('costlocker')['userId'] ?? 0;
            $this->costlockerUser = $this->database->findCostlockerUserById($id);
        }
        return $this->costlockerUser ?: new CostlockerUser();
    }

    private function transformFakturoidUser(FakturoidUser $u)
    {
        return [
            'person' => [
                'email' => $u->email,
                'full_name' => $u->data['full_name'],  
            ],
            'account' => [
                'slug' => $u->fakturoidAccount->slug,
                'name' => $u->fakturoidAccount->name,
            ],
        ];
    }
}
