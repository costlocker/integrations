<?php

namespace Costlocker\Integrations\Auth;

use League\OAuth2\Client\Token\AccessToken as OAuthToken;
use Costlocker\Integrations\Database\BasecampUser;
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
        $clUser = $this->getUser->getCostlockerUser();

        $user = $this->findUserInDb($apiUser['identity']['id']) ?: new BasecampUser();
        $user->id = $apiUser['identity']['id'];
        $user->data = $apiUser;
        $clUser->addBasecampUser($user);

        foreach ($apiUser['accounts'] as $apiAccount) {
            $account = $user->upsertAccount($apiAccount['id']);
            $account->name = $apiAccount['name'];
            $account->product = $apiAccount['product'];
            $account->urlApi = $apiAccount['href'];
            $account->urlApp = $apiAccount['app_href'];
        }

        $token = new AccessToken();
        $token->costlockerUser = $clUser;
        $token->basecampUser = $user;
        $token->accessToken = $apiToken->getToken();
        $token->refreshToken = $apiToken->getRefreshToken();
        $token->expiresAt = \DateTime::createFromFormat('U', $apiToken->getExpires());

        $this->entityManager->persist($clUser);
        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $user->id;
    }

    private function findUserInDb($id)
    {
        return $this->entityManager
            ->getRepository(BasecampUser::class)
            ->find($id);
    }
}
