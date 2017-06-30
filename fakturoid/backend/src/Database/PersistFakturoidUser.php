<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Entities\FakturoidAccount;
use Costlocker\Integrations\Entities\FakturoidUser;
use Costlocker\Integrations\Database\Database;
use Costlocker\Integrations\Auth\GetUser;

class PersistFakturoidUser
{
    private $database;
    private $getUser;

    public function __construct(Database $db, GetUser $u)
    {
        $this->database = $db;
        $this->getUser = $u;
    }

    public function __invoke(array $apiUser, array $apiAccount)
    {
        $account = $this->database->findFakturoidAccount($apiAccount['slug']) ?: new FakturoidAccount();
        $account->slug = $apiAccount['slug'];
        $account->name = $apiAccount['name'];

        $user = $this->database->findFakturoidUser($apiUser['email'], $apiAccount['slug']) ?: new FakturoidUser();
        $user->email = $apiUser['email'];
        $user->data = $apiUser;
        $user->fakturoidAccount = $account;
        $user->fakturoidId = $apiUser['id'];
        $user->updatedAt = new \DateTime();

        $clUser = $this->getUser->getCostlockerUser();
        $clUser->fakturoidUser = $user;

        $this->database->persist($account, $user, $clUser);
    }
}
