<?php

namespace Costlocker\Integrations\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Database\Event;

class PushSyncRequest
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function __invoke($type, array $request)
    {
        $event = new Event();
        $event->event = $type;
        $event->data = [
            'request' => $request,
        ];

        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }
}
