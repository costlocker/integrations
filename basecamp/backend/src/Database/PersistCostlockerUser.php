<?php

namespace Costlocker\Integrations\Database;

use League\OAuth2\Client\Token\AccessToken as OAuthToken;
use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\AccessToken;
use Doctrine\ORM\EntityManagerInterface;

class PersistCostlockerUser
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

        $user = $this->findUserInDb($apiUser['person']['email'], $company->id) ?: new CostlockerUser();
        $user->email = $apiUser['person']['email'];
        $user->costlockerCompany = $company;
        $user->data = $apiUser;

        $token = new AccessToken();
        $token->costlockerUser = $user;
        $token->accessToken = $apiToken->getToken();
        $token->refreshToken = $apiToken->getRefreshToken();
        $token->expiresAt = \DateTime::createFromFormat('U', $apiToken->getExpires());

        $this->entityManager->persist($company);
        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return [
            $user->id,
            count($user->basecampUsers) ? $user->getActiveUsers()->first()->id : null,
        ];
    }

    private function findCompanyInDb($idCompany)
    {
        return $this->entityManager->getRepository(CostlockerCompany::class)
            ->find($idCompany);
    }

    private function findUserInDb($email, $companyId)
    {
        return $this->entityManager->getRepository(CostlockerUser::class)
            ->findOneBy(['email' => $email, 'costlockerCompany' => $companyId]);
    }
}
