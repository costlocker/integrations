<?php

namespace Costlocker\Integrations\Auth;

use League\OAuth2\Client\Token\AccessToken as OAuthToken;
use Costlocker\Integrations\Database\CostlockerUser;
use Costlocker\Integrations\Database\BasecampUser;
use Costlocker\Integrations\Database\BasecampAccount;
use Costlocker\Integrations\Database\AccessToken;
use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;

class PersistBasecampUser
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke(array $apiUser, OAuthToken $apiToken)
    {
        $newUser = new BasecampUser();
        $newUser->id = $apiUser['identity']['id'];
        $newUser->data = $apiUser;
        $newUser->costlockerUser = $this->getCurrentCostlockerUser();
        $user = $this->findUserInDb($newUser) ?: $newUser;

        foreach ($apiUser['accounts'] as $apiAccount) {
            $account = new BasecampAccount();
            $account->id = $apiAccount['id'];
            $account->name = $apiAccount['name'];
            $account->product = $apiAccount['product'];
            $account->urlApi = $apiAccount['href'];
            $account->urlApp = $apiAccount['app_href'];
            $user->addAccount($account);
        }

        $token = new AccessToken();
        $token->costlockerUser = $newUser->costlockerUser;
        $token->basecampUser = $user;
        $token->accessToken = $apiToken->getToken();
        $token->refreshToken = $apiToken->getRefreshToken();
        $token->expiresAt = \DateTime::createFromFormat('U', $apiToken->getExpires());

        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();
    }

    private function findUserInDb(BasecampUser $user)
    {
        return $this->entityManager->getRepository(BasecampUser::class)
            ->find($user->id);
    }

    private function getCurrentCostlockerUser()
    {
        return $this->entityManager->getRepository(CostlockerUser::class)
            ->find($this->getUser->getCostlockerUserId());
    }
}
