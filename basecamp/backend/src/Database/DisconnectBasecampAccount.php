<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Entities\BasecampUser;

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
        $basecampUser = $costlockerUser->getUser($userId);
        $wasDeleted = $basecampUser ? true : false;
        $accountName = $userId;

        if ($basecampUser instanceof BasecampUser) {
            $accountName = $basecampUser->data['email_address'] ?? $userId;
            $basecampUser->deletedAt = new \DateTime();
            $this->entityManager->persist($basecampUser);
            $this->entityManager->flush();
            $this->getUser->checkDisconnectedBasecampUser($userId);
        }

        $this->logger->__invoke(
            Event::DISCONNECT_BASECAMP,
            ['costlocker' => $costlockerUser->id, 'basecamp' => $accountName, 'result' => $wasDeleted]
        );
        return $wasDeleted;
    }
}
