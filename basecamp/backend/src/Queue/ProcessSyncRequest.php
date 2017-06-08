<?php

namespace Costlocker\Integrations\Queue;

use Silex\Application;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Database\Event;

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

        try {
            $strategy = $this->getSynchronizer($requestEvent->data['type']);
            if ($strategy) {
                $result = $strategy($requestEvent->data['request']);
                $event->markStatus(Event::RESULT_SUCCESS, $result);
            }
        } catch (\Exception $e) {
            $event->markStatus(Event::RESULT_FAILURE, [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
        }

        $requestEvent->updatedAt = new \DateTime();
        $this->entityManager->persist($event);
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    private function getSynchronizer($eventType)
    {
        if ($eventType == Event::MANUAL_SYNC) {
            return new \Costlocker\Integrations\Basecamp\SyncProjectToBasecamp(
                $this->app['client.costlocker'],
                $this->app['client.basecamp'],
                $this->app['database']
            );
        } elseif ($eventType == Event::WEBHOOK_SYNC) {
            return new \Costlocker\Integrations\Basecamp\SyncWebhookToBasecamp(
                $this->app['client.basecamp'],
                $this->app['database']
            );
        }
    }
}
