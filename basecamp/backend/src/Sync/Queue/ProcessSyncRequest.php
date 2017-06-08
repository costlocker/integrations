<?php

namespace Costlocker\Integrations\Sync\Queue;

use Silex\Application;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Sync\SyncResult;
use Costlocker\Integrations\Events\EventsRepository;

class ProcessSyncRequest
{
    private $app;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var GetUser */
    private $getUser;
    /** @var EventsRepository */
    private $repository;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->entityManager = $app['orm.em'];
        $this->getUser = $app['client.user'];
        $this->repository = $app['database.events'];
    }

    public function __invoke()
    {
        $events = $this->repository->findUnprocessedEvents();
        foreach ($events as $event) {
            $this->processEvent($event);
        }
        return count($events);
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
        }

        $requestEvent->updatedAt = new \DateTime();

        $this->entityManager->persist($requestEvent);
        foreach ($events as $e) {
            $this->entityManager->persist($e);
        }
        $this->entityManager->flush();
    }

    private function getSynchronizer($eventType)
    {
        if ($eventType == Event::MANUAL_SYNC) {
            return new \Costlocker\Integrations\Sync\SyncProjectToBasecamp(
                $this->app['client.costlocker'],
                $this->app['client.basecamp'],
                $this->app['database']
            );
        } elseif ($eventType == Event::WEBHOOK_SYNC) {
            return new \Costlocker\Integrations\Sync\SyncWebhookToBasecamp(
                $this->app['client.basecamp'],
                $this->app['database']
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
