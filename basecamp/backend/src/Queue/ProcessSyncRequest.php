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

        if ($requestEvent->data['type'] == Event::MANUAL_SYNC) {
            $this->manualSync($requestEvent->data['request'], $event);
        } elseif ($requestEvent->data['type'] == Event::WEBHOOK_SYNC) {
            $this->webhookSync($requestEvent->data['request'], $event);
        }

        $requestEvent->updatedAt = new \DateTime();
        $this->entityManager->persist($event);
        $this->entityManager->persist($event);
        $this->entityManager->flush();
    }

    private function manualSync(array $jsonRequest, Event $event)
    {
        $json = new \Symfony\Component\HttpFoundation\ParameterBag($jsonRequest);
        $request = new \Costlocker\Integrations\Basecamp\SyncRequest();
        $request->account = $json->get('account');
        $request->costlockerProject = $json->get('costlockerProject');
        $isProjectLinked = $json->get('mode') == 'add';
        $request->updatedBasecampProject = $isProjectLinked ? $json->get('basecampProject') : null;
        $request->areTodosEnabled = $json->get('areTodosEnabled');
        if ($request->areTodosEnabled) {
            $request->isDeletingTodosEnabled = $json->get('isDeletingTodosEnabled');
            $request->isRevokeAccessEnabled = $json->get('isRevokeAccessEnabled');
        }

        try {
            $strategy = new \Costlocker\Integrations\Basecamp\SyncProjectToBasecamp(
                $this->app['client.costlocker'],
                $this->app['client.basecamp'],
                $this->app['database']
            );
            $result = $strategy($request);
            $event->markStatus(Event::RESULT_SUCCESS, $result);
        } catch (\Exception $e) {
            $event->markStatus(Event::RESULT_FAILURE, [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function webhookSync(array $webhook, Event $event)
    {
        try {
            $strategy = new \Costlocker\Integrations\Basecamp\SyncWebhookToBasecamp(
                $this->app['client.basecamp'],
                $this->app['database']
            );
            $data = $strategy(json_encode($webhook));
            $event->markStatus(Event::RESULT_SUCCESS, $data);
        } catch (\Exception $e) {
            $event->markStatus(Event::RESULT_FAILURE, [
                'exception' => get_class($e),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
