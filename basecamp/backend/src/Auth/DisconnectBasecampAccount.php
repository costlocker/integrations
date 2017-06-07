<?php

namespace Costlocker\Integrations\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;

class DisconnectBasecampAccount
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke($userId)
    {
        $costlockerUser = $this->getUser->getCostlockerUser();
        $wasDeleted = $costlockerUser->removeUser($userId);
        $this->entityManager->persist($costlockerUser);
        $this->entityManager->flush();
        $this->getUser->checkDisconnectedBasecampUser($userId);
        return $wasDeleted;
    }
}
