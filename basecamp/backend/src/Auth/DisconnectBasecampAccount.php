<?php

namespace Costlocker\Integrations\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Queue\EventsLogger;
use Costlocker\Integrations\Database\Event;

class DisconnectBasecampAccount
{
    private $entityManager;
    private $getUser;
    private $logger;

    public function __construct(EntityManagerInterface $em, GetUser $u, EventsLogger $l)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
        $this->logger = $l;
    }

    public function __invoke($userId)
    {
        $costlockerUser = $this->getUser->getCostlockerUser();
        $wasDeleted = $costlockerUser->removeUser($userId);
        $this->entityManager->persist($costlockerUser);
        $this->entityManager->flush();
        $this->getUser->checkDisconnectedBasecampUser($userId);

        $this->logger->__invoke(
            Event::DISCONNECT_BASECAMP,
            ['costlocker' => $costlockerUser->id, 'basecamp' => $userId, 'result' => $wasDeleted]
        );
        return $wasDeleted;
    }
}
