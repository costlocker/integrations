<?php

namespace Costlocker\Integrations\Sync\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Sync\SyncResult;
use Costlocker\Integrations\Events\EventsRepository;
use Costlocker\Integrations\Sync\ProcessEvent;
use Psr\Log\LoggerInterface;

class ProcessSyncRequest
{
    private $repository;
    private $processEvent;
    private $entityManager;
    private $logger;

    public function __construct(EventsRepository $r, ProcessEvent $p, EntityManagerInterface $em, LoggerInterface $l)
    {
        $this->repository = $r;
        $this->processEvent = $p;
        $this->entityManager = $em;
        $this->logger = $l;
    }

    public function __invoke($eventId)
    {
        $event = $this->repository->findSyncRequest($eventId);
        if ($event) {
            return $this->processEvent($event);
        }
        return 0;
    }

    private function processEvent(Event $requestEvent)
    {
        $event = new Event();
        $event->event = $requestEvent->data['type'];
        $event->data = [
            'request' => $requestEvent->data,
        ];
        $events = [];

        try {
            $results = $this->processEvent->__invoke($requestEvent);
            if (is_array($results)) {
                foreach ($results as $result) {
                    $events[] = $this->buildProjectEvent($event, $result);
                }
            } elseif (is_string($results)) {
                $requestEvent->markStatus(Event::RESULT_FAILURE, ['error' => $results]);
            }
        } catch (\Exception $e) {
            $events[] = $event;
            $event->markStatus(Event::RESULT_FAILURE, [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            $this->logger->error($e);
        }

        $requestEvent->updatedAt = new \DateTime();

        $this->entityManager->persist($requestEvent);
        foreach ($events as $e) {
            $this->entityManager->persist($e);
        }
        $this->entityManager->flush();

        return count($events);
    }

    private function buildProjectEvent(Event $event, SyncResult $result)
    {
        $projectEvent = clone $event;
        $projectEvent->basecampProject = $result->mappedProject;
        $projectEvent->markStatus($result->getResultStatus(), $result->toArray());
        return $projectEvent;
    }
}
