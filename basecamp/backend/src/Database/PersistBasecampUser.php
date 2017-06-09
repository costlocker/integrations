<?php

namespace Costlocker\Integrations\Database;

use League\OAuth2\Client\Token\AccessToken as OAuthToken;
use Costlocker\Integrations\Entities\BasecampUser;
use Costlocker\Integrations\Entities\AccessToken;
use Costlocker\Integrations\Entities\BasecampAccount;
use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;

class PersistBasecampUser
{
    private $entityManager;
    private $getUser;
    private $allowedProducts;

    public function __construct(EntityManagerInterface $em, GetUser $u, array $allowedProducts)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
        $this->allowedProducts = $allowedProducts;
    }

    public function __invoke(array $apiUser, OAuthToken $apiToken)
    {
        $clUser = $this->getUser->getCostlockerUser();
        $basecampUserId = null;

        foreach ($apiUser['accounts'] as $apiAccount) {
            if (!in_array($apiAccount['product'], $this->allowedProducts)) {
                continue;
            }
            // shared account
            $account = $this->findAccountInDb($apiAccount['id']) ?: new BasecampAccount();
            $account->id = $apiAccount['id'];
            $account->name = $apiAccount['name'];
            $account->product = $apiAccount['product'];
            $account->urlApi = $apiAccount['href'];
            $account->urlApp = $apiAccount['app_href'];
            $this->entityManager->persist($account);

            // connect user + account
            $basecampUser = $this->findUserInDb($clUser->id, $account->id, $apiUser['identity']['id'])
                ?: new BasecampUser();
            $basecampUser->basecampIdentityId = $apiUser['identity']['id'];
            $basecampUser->data = $apiUser['identity'];
            $basecampUser->basecampAccount = $account;
            $basecampUser->costlockerUser = $clUser;
            $basecampUser->deletedAt = null;
            $this->entityManager->persist($basecampUser);
            if (!$basecampUserId) {
                $basecampUserId = $basecampUser->id;
            }
        }

        $token = new AccessToken($apiToken);
        $token->costlockerUser = $clUser;
        $token->basecampIdentityId = $apiUser['identity']['id'];

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $basecampUserId;
    }

    private function findAccountInDb($id)
    {
        return $this->entityManager
            ->getRepository(BasecampAccount::class)
            ->find($id);
    }

    private function findUserInDb($clUserId, $bcAccountId, $bcIdentityId)
    {
        return $this->entityManager
            ->getRepository(BasecampUser::class)
            ->findOneBy([
                'costlockerUser' => $clUserId,
                'basecampAccount' => $bcAccountId,
                'basecampIdentityId' => $bcIdentityId,
            ]);
    }
}
