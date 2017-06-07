<?php

namespace Costlocker\Integrations\Auth;

use League\OAuth2\Client\Token\AccessToken as OAuthToken;
use Costlocker\Integrations\Database\CostlockerCompany;
use Costlocker\Integrations\Database\CostlockerUser;
use Costlocker\Integrations\Database\AccessToken;
use Doctrine\ORM\EntityManagerInterface;

class PersistsCostlockerUser
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function __invoke(array $apiUser, OAuthToken $apiToken)
    {
        $company = $this->findCompanyInDb($apiUser['company']['id']) ?: new CostlockerCompany();
        $company->id = $apiUser['company']['id'];

        $newUser = new CostlockerUser();
        $newUser->email = $apiUser['person']['email'];
        $newUser->costlockerCompany = $company;
        $newUser->data = $apiUser;
        $user = $this->findUserInDb($newUser) ?: $newUser;

        $token = new AccessToken();
        $token->costlockerUser = $user;
        $token->accessToken = $apiToken->getToken();
        $token->refreshToken = $apiToken->getRefreshToken();
        $token->expiresAt = \DateTime::createFromFormat('U', $apiToken->getExpires());

        $this->entityManager->persist($company);
        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $user->id;
    }

    private function findCompanyInDb($idCompany)
    {
        return $this->entityManager->getRepository(CostlockerCompany::class)
            ->find($idCompany);
    }

    private function findUserInDb(CostlockerUser $user)
    {
        return $this->entityManager->getRepository(CostlockerUser::class)
            ->findOneBy(['email' => $user->email, 'costlockerCompany' => $user->costlockerCompany->id]);
    }
}
