<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;

class RefreshAccessTokens
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }
    
    public function __invoke($expiresInterval, $areTokensRefreshed)
    {
        $expiredTokens = $this->findExpired($expiresInterval);
        if ($areTokensRefreshed) {
            $this->refreshTokens($expiredTokens);
        }
        return $expiredTokens;
    }

    private function findExpired($expiresInterval)
    {
        $sql =<<<SQL
            WITH active AS (
              SELECT DISTINCT bc_identity_id
              FROM bc_projects
              JOIN bc_cl_users ON bc_cl_users.id = bc_projects.bc_user_id
              WHERE bc_projects.deleted_at IS NULL
            )
            SELECT bc_identity_id, max(expires_at), max(id) as last_token_id
            FROM oauth2_tokens
            WHERE bc_identity_id IN (SELECT * FROM active)
            GROUP BY bc_identity_id
            HAVING max(expires_at) < NOW() + INTERVAL '{$expiresInterval}'
               AND max(expires_at) > NOW()
SQL;
        $query = $this->entityManager->getConnection()->executeQuery($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function refreshTokens(array $expiredTokens)
    {
        
    }
}
