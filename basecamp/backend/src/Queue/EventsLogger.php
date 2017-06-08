<?php

namespace Costlocker\Integrations\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Database\Event;

class EventsLogger
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke($eventType, array $data)
    {
        $event = new Event();
        $event->event = $eventType;
        $event->data = $data;
        $event->costlockerUser = $this->getUser->getCostlockerUser(false);

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
