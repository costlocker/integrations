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
        return new JsonResponse([
            'costlocker' => $clUser->data,
            'basecamp' => $bcUser->data,
            'settings' => [
                'sync' => $clUser->costlockerCompany ? $clUser->costlockerCompany->getSettings() : null,
                'users' => $this->getConnectedUsersAndAccounts()
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
            JOIN bu.accounts ba
            WHERE cu.costlockerCompany = :company
DQL;
        $params = [
            'company' => $costlockerUser->costlockerCompany->id,
        ];
        return array_map(
            function (CostlockerUser $u) {
                return [
                    'person' => $u->data['person'],
                    'accounts' => array_reduce(
                        array_map(
                            function (BasecampUser $b) {
                                return array_map(
                                    function (BasecampAccount $a) use ($b) {
                                        return [
                                            'id' => $a->id,
                                            'name' => $a->name,
                                            'product' => $a->product,
                                            'urlApp' => $a->urlApp,
                                            'identity' => $b->data['identity'],
                                        ];
                                    },
                                    $b->accounts->toArray()
                                );
                            },
                            $u->basecampUsers->toArray()
                        ),
                        'array_merge',
                        []
                    ),
                ];
            },
            $this->entityManager->createQuery($dql)->execute($params)
        );
    }

    public function getCostlockerAccessToken()
    {
        $sql =<<<SQL
            SELECT access_token
            FROM oauth2_tokens
            WHERE cl_user_id = :cl AND bc_user_id IS NULL
            ORDER BY id DESC
            LIMIT 1
SQL;
        $params = [
            'cl' => $this->getCostlockerUser()->id,
        ];
        $query = $this->entityManager->getConnection()->executeQuery($sql, $params);
        return $query->fetchColumn();
    }

    public function getBasecampAccessToken($accountId)
    {
        $sql =<<<SQL
            SELECT access_token
            FROM oauth2_tokens
            JOIN bc_accounts ON oauth2_tokens.bc_user_id = bc_accounts.bc_user_id
            WHERE bc_accounts.id = :id
            ORDER BY oauth2_tokens.id DESC
            LIMIT 1
SQL;
        $params = [
            'id' => $accountId,
        ];
        $query = $this->entityManager->getConnection()->executeQuery($sql, $params);
        return $query->fetchColumn();
    }

    public function getBasecampAccount($accountId)
    {
        return $this->entityManager
            ->getRepository(BasecampAccount::class)
            ->find($accountId) ?: new BasecampAccount();
    }
}
