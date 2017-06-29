<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Entities\FakturoidAccount;
use Costlocker\Integrations\Entities\FakturoidUser;
use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;

class PersistFakturoidUser
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke(array $apiUser, array $apiAccount)
    {
        $account = $this->findAccountInDb($apiAccount['slug']) ?: new FakturoidAccount();
        $account->slug = $apiAccount['slug'];
        $account->name = $apiAccount['name'];

        $user = $this->findUserInDb($apiUser['email'], $apiAccount['slug']) ?: new FakturoidUser();
        $user->email = $apiUser['email'];
        $user->data = $apiUser;
        $user->fakturoidAccount = $account;
        $user->fakturoidId = $apiUser['id'];
        $user->updatedAt = new \DateTime();

        $clUser = $this->getUser->getCostlockerUser();
        $clUser->fakturoidUser = $user;

        $this->entityManager->persist($account);
        $this->entityManager->persist($user);
        $this->entityManager->persist($clUser);
        $this->entityManager->flush();

        return $user->id;
    }

    private function findAccountInDb($slug)
    {
        return $this->entityManager
            ->getRepository(FakturoidAccount::class)
            ->findOneBy(['slug' => $slug]);
    }

    private function findUserInDb($email, $slug)
    {
        $dql =<<<DQL
            SELECT u
            FROM Costlocker\Integrations\Entities\FakturoidUser u
            JOIN u.fakturoidAccount a
            WHERE u.email = :email AND a.slug = :slug
DQL;
        $params = [
            'email' => $email,
            'slug' => $slug,
        ];
        $entites = $this->entityManager->createQuery($dql)->execute($params);
        return array_shift($entites);
    }
}
