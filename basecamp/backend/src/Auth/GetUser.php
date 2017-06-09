<?php

namespace Costlocker\Integrations\Auth;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\BasecampUser;
use Costlocker\Integrations\Entities\BasecampAccount;

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
        $connectedAccounts = $this->getConnectedUsersAndAccounts();
        return new JsonResponse([
            'costlocker' => $clUser->data,
            'basecamp' => $bcUser->data,
            'settings' => [
                'accounts' => $connectedAccounts[$clUser->id]['accounts'] ?? [],
                'sync' => $clUser->costlockerCompany ? $clUser->costlockerCompany->getSettings() : null,
                'users' => array_values($connectedAccounts),
            ],
        ]);
    }

    public function overrideCostlockerUser(CostlockerUser $user = null)
    {
        $this->costlockerUser = $user;
    }

    public function getCostlockerUser($returnNullObject = true)
    {
        if (!$this->costlockerUser) {
            $userId = $this->session->get('costlocker')['userId'] ?? 0;
            $user = $this->entityManager->getRepository(CostlockerUser::class)
                ->find($userId);
            $this->costlockerUser = $user;
        }
        return $this->costlockerUser ?: ($returnNullObject ? new CostlockerUser() : null);
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

    public function checkDisconnectedBasecampUser($userId)
    {
        if ($userId == $this->session->get('basecamp')['userId']) {
            $this->session->remove('basecamp');
        }
    }

    private function getConnectedUsersAndAccounts()
    {
        $costlockerUser = $this->getCostlockerUser();
        if (!$costlockerUser->id) {
            return [];
        }
        $dql =<<<DQL
            SELECT cu, bu, ba
            FROM Costlocker\Integrations\Entities\CostlockerUser cu
            JOIN cu.basecampUsers bu
            JOIN bu.basecampAccount ba
            WHERE cu.costlockerCompany = :company AND bu.deletedAt IS NULL
DQL;
        $params = [
            'company' => $costlockerUser->costlockerCompany->id,
        ];
        $entities = $this->entityManager->createQuery($dql)->execute($params);

        $users = [];
        foreach ($entities as $u) {
            $users[$u->id] = [
                'person' => $u->data['person'],
                'accounts' =>
                    array_map(
                        function (BasecampUser $b) {
                            return [
                                'id' => $b->id,
                                'name' => $b->basecampAccount->name,
                                'product' => $b->basecampAccount->product,
                                'urlApp' => $b->basecampAccount->urlApp,
                                'identity' => $b->data,
                            ];
                        },
                        $u->basecampUsers->toArray()
                    ),
            ];
        }
        return $users;
    }

    public function getCostlockerAccessToken()
    {
        $sql =<<<SQL
            SELECT access_token
            FROM oauth2_tokens
            WHERE cl_user_id = :cl AND bc_identity_id IS NULL
            ORDER BY id DESC
            LIMIT 1
SQL;
        $params = [
            'cl' => $this->getCostlockerUser()->id,
        ];
        $query = $this->entityManager->getConnection()->executeQuery($sql, $params);
        return $query->fetchColumn();
    }

    public function getBasecampAccessToken($basecampUserId)
    {
        $sql =<<<SQL
            SELECT access_token
            FROM oauth2_tokens
            JOIN cl_users ON oauth2_tokens.cl_user_id = cl_users.id
            JOIN bc_cl_users ON bc_cl_users.cl_user_id = cl_users.id
            WHERE bc_cl_users.id = :id AND bc_cl_users.deleted_at IS NULL
              AND bc_cl_users.bc_identity_id = oauth2_tokens.bc_identity_id
            ORDER BY oauth2_tokens.id DESC
            LIMIT 1
SQL;
        $params = [
            'id' => $basecampUserId,
        ];
        $query = $this->entityManager->getConnection()->executeQuery($sql, $params);
        return $query->fetchColumn();
    }

    public function getBasecampAccount($basecampUserId)
    {
        $dql =<<<DQL
            SELECT ba
            FROM Costlocker\Integrations\Entities\BasecampAccount ba
            JOIN ba.costlockerUsers bu
            WHERE bu.id = :id AND bu.deletedAt IS NULL
DQL;
        $params = [
            'id' => $basecampUserId,
        ];
        $entities = $this->entityManager->createQuery($dql)->execute($params);
        return $entities ? array_shift($entities) : new BasecampAccount();
    }
}
