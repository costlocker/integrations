<?php

namespace Costlocker\Integrations\Sync\Queue;

use Silex\Application;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Sync\SyncResult;
use Costlocker\Integrations\Events\EventsRepository;
use Psr\Log\LoggerInterface;

class ProcessSyncRequest
{
    private $app;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var GetUser */
    private $getUser;
    /** @var EventsRepository */
    private $repository;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->entityManager = $app['orm.em'];
        $this->getUser = $app['client.user'];
        $this->repository = $app['database.events'];
        $this->logger = $app['logger'];
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
        $this->getUser->overrideCostlockerUser($requestEvent->costlockerUser);

        $event = new Event();
        $event->event = $requestEvent->data['type'];
        $event->data = [
            'request' => $requestEvent->data,
        ];
        $events = [];

        try {
            $strategy = $this->getSynchronizer($requestEvent->data['type']);
            if ($strategy) {
                $results = $strategy($requestEvent->data['request']);
                foreach ($results as $result) {
                    $events[] = $this->buildProjectEvent($event, $result);
                }
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

    private function getSynchronizer($eventType)
    {
        $synchronizer = new \Costlocker\Integrations\Sync\Synchronizer(
            $this->app['client.basecamp'],
            $this->app['database']
        );

        if ($eventType == Event::MANUAL_SYNC) {
            return new \Costlocker\Integrations\Sync\SyncProjectToBasecamp(
                $this->app['client.costlocker'],
                $synchronizer
            );
        } elseif ($eventType == Event::WEBHOOK_SYNC) {
            return new \Costlocker\Integrations\Sync\SyncWebhookToBasecamp(
                $this->app['database.companies'],
                $synchronizer
            );
        }
    }

    private function buildProjectEvent(Event $event, SyncResult $result)
    {
        $projectEvent = clone $event;
        $projectEvent->basecampProject = $result->mappedProject;
        $projectEvent->markStatus($result->getResultStatus(), $result->toArray());
        return $projectEvent;
    }
}
