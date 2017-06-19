<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Sync\Connect\Costlocker;
use Costlocker\Integrations\Sync\Connect\Basecamp;

class ProcessEvent
{
    private $services;

    public function __construct($services)
    {
        $this->services = $services;
    }

    public function __invoke(Event $e)
    {
        $webhookUrl = $e->data['webhookUrl'];
        $strategy = $this->getSynchronizer($e->data['type'], $webhookUrl);
        return $strategy($e->data['request'], $e->costlockerUser, $webhookUrl);
    }

    private function getSynchronizer($eventType, $webhookUrl)
    {
        $synchronizer = new Synchronizer(
            new Costlocker(
                $this->services['client.costlocker'],
                $this->services['client.user'],
                $this->services['events.logger'],
                $webhookUrl
            ),
            new Basecamp(
                $this->services['client.basecamp'],
                $this->services['events.logger'],
                $webhookUrl
            ),
            $this->services['database']
        );

        if ($eventType == Event::MANUAL_SYNC) {
            return new ProcessManualRequest($synchronizer);
        } elseif ($eventType == Event::WEBHOOK_BASECAMP) {
            return new ProcessAggregatedBasecampWebhook($this->services['database'], $synchronizer);
        } elseif ($eventType == Event::WEBHOOK_SYNC) {
            return new ProcessApiWebhook(
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
