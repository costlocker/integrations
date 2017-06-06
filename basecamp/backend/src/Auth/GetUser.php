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
        return new JsonResponse([
            'basecamp' => $this->getBasecampUser()->data,
            'costlocker' => $this->getCostlockerUser()->data,
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
