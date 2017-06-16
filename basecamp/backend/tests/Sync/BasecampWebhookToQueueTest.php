<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Entities\Event;

class BasecampWebhookToQueueTest extends GivenSynchronizer
{
    protected $eventType = Event::WEBHOOK_SYNC;
    const BASECAMP_ID = 123456;

    private $isBasecampProjectMappped = true;
    private $isBasecampSynchronizationAllowed = true;

    public function testPushBasecampWebhookToQueue()
    {
        $this->givenBasecampWebhook('todo_created.json');
        $this->eventsLogger->shouldReceive('__invoke')
            ->once()
            ->with(
                Event::WEBHOOK_BASECAMP,
                [
                    'costlockerProject' => 1,
                    'basecampProject' => self::BASECAMP_ID,
                    'basecampEvent' => 'todo_created',
                ],
                m::type(\Costlocker\Integrations\Entities\BasecampProject::class)
            );
        $this->processWebhook();
    }

    public function testIgnoreUnknownBasecampProject()
    {
        $this->givenBasecampWebhook('todo_created.json');
        $this->isBasecampProjectMappped = false;
        $this->processWebhook('Unmapped or disabled');
    }

    public function testIgnoreProjectWithDisabledSync()
    {
        $this->givenBasecampWebhook('todo_created.json');
        $this->isBasecampSynchronizationAllowed = false;
        $this->processWebhook('Unmapped or disabled');
    }

    public function testIgnoreUnrelatedBasecampWebhooks()
    {
        $this->givenBasecampWebhook('message_created.json');
        $this->eventsLogger->shouldReceive('__invoke')->never();
        $this->processWebhook('Not allowed');
    }

    protected function processWebhook($expectedResult = null)
    {
        if ($this->isBasecampProjectMappped) {
            $this->whenProjectIsMapped(
                self::BASECAMP_ID,
                [],
                ['areTasksEnabled' => $this->isBasecampSynchronizationAllowed]
            );
        }
        $this->synchronize($expectedResult);
    }

    private function givenBasecampWebhook($file)
    {
        parent::givenWebhook(
            "basecamp/{$file}",
            ['user-agent'  => ['Basecamp3 Webhook']]
        );
    }
}
