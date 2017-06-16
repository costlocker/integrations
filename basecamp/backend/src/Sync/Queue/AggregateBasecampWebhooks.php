<?php

namespace Costlocker\Integrations\Sync\Queue;

use Costlocker\Integrations\Events\EventsRepository;
use Costlocker\Integrations\Sync\Queue\PushSyncRequest;
use Costlocker\Integrations\Entities\Event;

class AggregateBasecampWebhooks
{
    private $repository;
    private $pushEvent;

    public function __construct(EventsRepository $r, PushSyncRequest $p)
    {
        $this->repository = $r;
        $this->pushEvent = $p;
    }

    public function __invoke($secondsDelay)
    {
        $events = $this->repository->findBasecampWebhooks($secondsDelay);
        foreach ($events as $project) {
            $eventIds = json_decode($project['events']);
            $this->pushEvent->__invoke(Event::WEBHOOK_BASECAMP, ['costlockerProject' => $project['id']]);
            $this->repository->markEventsAsProcessed($eventIds);
        }
        return $events;
    }
}
