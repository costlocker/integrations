<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Database\Database;

class PersistCostlockerUser
{
    private $database;

    public function __construct(Database $db)
    {
        $this->database = $db;
    }

    public function __invoke(array $apiUser)
    {
        $user = $this->database->findCostlockerUser($apiUser['person']['email'], $apiUser['company']['id'])
            ?: new CostlockerUser();
        $user->email = $apiUser['person']['email'];
        $user->costlockerCompany = $apiUser['company']['id'];
        $user->data = $apiUser;
        $user->updatedAt = new \DateTime();

        $this->database->persist($user);

        return $user->id;
    }
}
