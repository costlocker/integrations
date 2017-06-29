<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Entities\CostlockerUser;
use Doctrine\ORM\EntityManagerInterface;

class PersistCostlockerUser
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function __invoke(array $apiUser)
    {
        $user = $this->findUserInDb($apiUser['person']['email'], $apiUser['company']['id']) ?: new CostlockerUser();
        $user->email = $apiUser['person']['email'];
        $user->costlockerCompany = $apiUser['company']['id'];
        $user->data = $apiUser;
        $user->updatedAt = new \DateTime();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user->id;
    }

    private function findUserInDb($email, $companyId)
    {
        return $this->entityManager->getRepository(CostlockerUser::class)
            ->findOneBy(['email' => $email, 'costlockerCompany' => $companyId]);
    }
}
