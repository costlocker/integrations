<?php

namespace Costlocker\Integrations\Events;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Entities\BasecampProject;

class EventsLogger
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke($eventType, array $data, BasecampProject $p = null)
    {
        $event = new Event();
        $event->event = $eventType;
        $event->data = $data;
        $event->costlockerUser = $this->getUser->getCostlockerUser(false);
        $event->basecampProject = $p;

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
