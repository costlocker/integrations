<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\Event;

class ProcessEvent
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function __invoke(Event $e)
    {
        $strategy = $this->getSynchronizer($e->data['type'], $e->data['request']['webhookUrl']);
        return $strategy($e->data['request'], $e->costlockerUser);
    }

    private function getSynchronizer($eventType, $webhookUrl)
    {
        $synchronizer = new Synchronizer(
            $this->services['client.costlocker'],
            $this->services['client.user'],
            $this->services['client.basecamp'],
            $this->services['database'],
            $this->services['events.logger'],
            $webhookUrl
        );

        if ($eventType == Event::MANUAL_SYNC) {
            return new SyncProjectToBasecamp($synchronizer);
        } elseif ($eventType == Event::WEBHOOK_BASECAMP) {
            return new SyncProjectToCostlocker($this->services['database'], $synchronizer);
        } elseif ($eventType == Event::WEBHOOK_SYNC) {
            return new SyncWebhookToBasecamp(
                $this->services['database'],
                $synchronizer,
                $this->services['events.logger']
            );
        } else {
            return function () {
                return [];
            };
        }
    }
}
