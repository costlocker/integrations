<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Database\CostlockerUser;
use Costlocker\Integrations\Database\BasecampUser;
use Costlocker\Integrations\Database\BasecampAccount;

class GetUser
{
    private $session;
    private $entityManager;
    private $costlockerUser;
    private $basecampUser;

    public function __construct(SessionInterface $s, EntityManagerInterface $em)
    {
        $this->session = $s;
        $this->entityManager = $em;
    }

    public function __invoke()
    {
        $clUser = $this->getCostlockerUser();
        $bcUser = $this->getBasecampUser();
        return new JsonResponse([
            'costlocker' => $clUser->data,
            'basecamp' => $bcUser->data,
            'availableAccounts' => $this->getAvailableBasecampAccounts()
        ]);
    }

    public function getCostlockerUser(): CostlockerUser
    {
        if (!$this->costlockerUser) {
            $userId = $this->session->get('costlocker')['userId'] ?? 0;
            $user = $this->entityManager->getRepository(CostlockerUser::class)
                ->find($userId);
            $this->costlockerUser = $user;
        }
        return $this->costlockerUser ?: new CostlockerUser();
    }

    public function getBasecampUser(): BasecampUser
    {
        if (!$this->basecampUser) {
            $userId = $this->session->get('basecamp')['userId'] ?? 0;
            $user = $this->entityManager->getRepository(BasecampUser::class)
                ->find($userId);
            $this->basecampUser = $user;
        }
        return $this->basecampUser ?: new BasecampUser();
    }

    private function getAvailableBasecampAccounts()
    {
        $costlockerUser = $this->getCostlockerUser();
        if (!$costlockerUser->idTenant) {
            return [];
        }
        $dql =<<<DQL
            SELECT b
            FROM Costlocker\Integrations\Database\BasecampAccount b
            JOIN b.basecampUser bu
            JOIN bu.costlockerUsers cu
            WHERE cu.idTenant = :tenant
DQL;
        $params = [
            'tenant' => $costlockerUser->idTenant,
        ];
        return $this->entityManager->createQuery($dql)->execute($params);
    }

    public function getCostlockerAccessToken()
    {
        $sql =<<<SQL
            SELECT access_token
            FROM oauth2_token
            WHERE costlocker_user_id = :cl AND basecamp_user_id IS NULL
            ORDER BY id DESC
            LIMIT 1
SQL;
        $params = [
            'cl' => $this->getCostlockerUser()->id,
        ];
        $query = $this->entityManager->getConnection()->executeQuery($sql, $params);
        return $query->fetchColumn();
    }

    public function getBasecampAccessToken()
    {
        $sql =<<<SQL
            SELECT access_token
            FROM oauth2_token
            WHERE costlocker_user_id = :cl AND basecamp_user_id = :bc
            ORDER BY id DESC
            LIMIT 1
SQL;
        $params = [
            'cl' => $this->getCostlockerUser()->id,
            'bc' => $this->getBasecampUser()->id,
        ];
        $query = $this->entityManager->getConnection()->executeQuery($sql, $params);
        return $query->fetchColumn();
    }

    public function getBasecampAccount($accountId)
    {
        return $this->getBasecampUser()->getAccount($accountId) ?: new BasecampAccount();
    }
}
