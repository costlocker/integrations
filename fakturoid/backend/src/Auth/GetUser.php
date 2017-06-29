<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\FakturoidUser;

class GetUser
{
    private $session;
    private $entityManager;

    private $costlockerUser;

    public function __construct(SessionInterface $s, EntityManagerInterface $em)
    {
        $this->session = $s;
        $this->entityManager = $em;
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
            $dql =<<<DQL
                SELECT cu, fu, fa
                FROM Costlocker\Integrations\Entities\CostlockerUser cu
                LEFT JOIN cu.fakturoidUser fu
                LEFT JOIN fu.fakturoidAccount fa
                WHERE cu.id = :id
DQL;
            $params = [
                'id' => $this->session->get('costlocker')['userId'] ?? 0
            ];
            $entities = $this->entityManager->createQuery($dql)->execute($params);
            $this->costlockerUser = array_shift($entities);
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
