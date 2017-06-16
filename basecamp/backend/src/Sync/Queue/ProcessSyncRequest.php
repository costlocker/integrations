<?php

namespace Costlocker\Integrations\Sync\Queue;

use Silex\Application;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Sync\SyncResult;
use Costlocker\Integrations\Events\EventsRepository;
use Psr\Log\LoggerInterface;

class ProcessSyncRequest
{
    private $app;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var EventsRepository */
    private $repository;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->entityManager = $app['orm.em'];
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
        $event = new Event();
        $event->event = $requestEvent->data['type'];
        $event->data = [
            'request' => $requestEvent->data,
        ];
        $events = [];

        try {
            $strategy = $this->getSynchronizer($requestEvent->data['type'], $requestEvent->data['request']['webhookUrl']);
            if ($strategy) {
                $results = $strategy($requestEvent->data['request'], $requestEvent->costlockerUser);
                if (is_array($results)) {
                    foreach ($results as $result) {
                        $events[] = $this->buildProjectEvent($event, $result);
                    }
                } elseif (is_string($results)) {
                    $requestEvent->markStatus(Event::RESULT_FAILURE, ['error' => $results]);
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

    private function getSynchronizer($eventType, $webhookUrl)
    {
        $synchronizer = new \Costlocker\Integrations\Sync\Synchronizer(
            $this->app['client.costlocker'],
            $this->app['client.user'],
            $this->app['client.basecamp'],
            $this->app['database'],
            $this->app['events.logger'],
            $webhookUrl
        );

        if ($eventType == Event::MANUAL_SYNC) {
            return new \Costlocker\Integrations\Sync\SyncProjectToBasecamp($synchronizer);
        } elseif ($eventType == Event::WEBHOOK_BASECAMP) {
            return new \Costlocker\Integrations\Sync\SyncProjectToCostlocker($synchronizer);
        } elseif ($eventType == Event::WEBHOOK_SYNC) {
            return new \Costlocker\Integrations\Sync\SyncWebhookToBasecamp(
                $this->app['database.companies'],
                $synchronizer,
                $this->app['events.logger']
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
