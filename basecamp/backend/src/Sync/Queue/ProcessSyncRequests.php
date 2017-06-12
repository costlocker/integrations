<?php

namespace Costlocker\Integrations\Sync\Queue;

use Costlocker\Integrations\Events\EventsRepository;
use Symfony\Component\Process\Process;

class ProcessSyncRequests
{
    private $repository;
    private $eventCommand;

    public function __construct(EventsRepository $r, $eventCommand)
    {
        $this->repository = $r;
        $this->eventCommand = $eventCommand;
    }

    public function __invoke($presenter)
    {
        $events = $this->repository->findUnprocessedEvents();
        foreach ($events as $eventId) {
            $this->processEvent($eventId, $presenter);
        }
        return count($events);
    }

    private function processEvent($eventId, $presenter)
    {
        try {
            $presenter($eventId, 'start');
            $process = new Process("{$this->eventCommand} {$eventId}");
            $process->run();
            $presenter($eventId, "{$process->getOutput()}{$process->getErrorOutput()}");
        } catch (\Exception $e) {
            $presenter($eventId, "Error: {$e->getMessage()}");
        }
    }
}
