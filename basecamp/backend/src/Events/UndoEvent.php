<?php

namespace Costlocker\Integrations\Events;

use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Events\EventsRepository;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Database\ProjectsDatabase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Api\ResponseHelper;

class UndoEvent
{
    private $events;
    private $projects;
    private $logger;

    public function __construct(EventsRepository $r, ProjectsDatabase $p, EventsLogger $l)
    {
        $this->events = $r;
        $this->projects = $p;
        $this->logger = $l;
    }

    public function __invoke($eventId)
    {
        $event = $this->events->findEvent($eventId);
        if (!$event || $event->event != Event::DISCONNECT_PROJECT) {
            return ResponseHelper::error("Undo is not available for the event {$eventId}");
        }

        $projectId = $event->getDisconnectedProjectId();
        $project = $this->projects->findByInternalId($projectId);

        if (!$project) {
            return ResponseHelper::error("Project cannot be reverted");
        }

        $this->projects->undoDisconnect($project);
        $this->logger->__invoke(
            Event::DISCONNECT_PROJECT | Event::UNDO_ACTION,
            ['undo' => $eventId],
            $project
        );
        return new JsonResponse();
    }
}
