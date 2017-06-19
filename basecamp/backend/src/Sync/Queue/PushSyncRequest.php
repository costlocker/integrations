<?php

namespace Costlocker\Integrations\Sync\Queue;

use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Events\EventsLogger;

class PushSyncRequest
{
    private $logger;

    public function __construct(EventsLogger $l)
    {
        $this->logger = $l;
    }

    public function __invoke($eventType, array $request, $webhookUrl)
    {
        $this->logger->__invoke(
            Event::SYNC_REQUEST,
            [
                'type' => $eventType,
                'webhookUrl' => $webhookUrl,
                'request' => $request,
            ]
        );
    }
}
