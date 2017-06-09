<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Costlocker\Integrations\Entities\AccessToken;
use Psr\Log\LoggerInterface;

class RefreshAccessTokens
{
    private $entityManager;
    private $provider;
    private $logger;

    public function __construct(EntityManagerInterface $em, AbstractProvider $p, LoggerInterface $l)
    {
        $this->entityManager = $em;
        $this->provider = $p;
        $this->logger = $l;
    }
    
    public function __invoke($expiresInterval, $isDryRun, $presenter)
    {
        $expiredTokens = $this->findExpired($expiresInterval);
        $this->refreshTokens($expiredTokens, $isDryRun, $presenter);
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
            SELECT bc_identity_id, max(expires_at) as expires_at, max(id) as id
            FROM oauth2_tokens
            WHERE bc_identity_id IN (SELECT * FROM active)
            GROUP BY bc_identity_id
            HAVING max(expires_at) < NOW() + INTERVAL '{$expiresInterval}'
               AND max(expires_at) > NOW()
SQL;
        $query = $this->entityManager->getConnection()->executeQuery($sql);
        return $query->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function refreshTokens(array $expiredTokens, $isDryRun, $presenter)
    {
        foreach ($expiredTokens as $token) {
            if ($isDryRun) {
                $presenter($token, 'dry-run');
                continue;
            }
            try {
                $entity = $this->entityManager
                    ->getRepository(AccessToken::class)
                    ->find($token['id']);
                $this->refreshToken($entity);
                $presenter($token, 'refreshed');
            } catch (IdentityProviderException $e) {
                $presenter($token, "{$e->getMessage()}", true);
            } catch (\Exception $e) {
                throw $e;
                $presenter($token, "{$e->getMessage()}", true);
                $this->logger->error($e);
            }
        }
    }

    public function refreshToken(AccessToken $expiringToken)
    {
        $newToken = $this->provider->getAccessToken('refresh_token', [
            'refresh_token' => $expiringToken->refreshToken,
        ]);

        $token = new AccessToken($newToken);
        if (!$token->refreshToken) {
            $token->refreshToken = $expiringToken->refreshToken;
        }
        $token->costlockerUser = $expiringToken->costlockerUser;
        $token->basecampIdentityId = $expiringToken->basecampIdentityId;
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }
}
