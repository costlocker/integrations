<?php

namespace Costlocker\Integrations\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Database\Event;

class PushSyncRequest
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke($eventType, array $request)
    {
        $event = new Event();
        $event->event = Event::SYNC_REQUEST;
        $event->costlockerUser = $this->getUser->getCostlockerUser(false);
        $event->data = [
            'type' => $eventType,
            'request' => $request,
        ];

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
