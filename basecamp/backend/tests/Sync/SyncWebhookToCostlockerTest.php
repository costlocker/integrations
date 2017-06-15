<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Database\CompaniesRepository;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class SyncWebhookToCostlockerTest extends GivenCostlockerToBasecampSynchronizer
{
    const BASECAMP_ID = 123456;

    private $eventsLogger;
    private $isBasecampProjectMappped = true;

    public function setUp()
    {
        parent::setUp();
        $this->eventsLogger = m::mock(EventsLogger::class);
    }
    
    protected function createSynchronizer(Synchronizer $s)
    {
        $companiesRepository = m::mock(CompaniesRepository::class);
        return new SyncWebhookToBasecamp($companiesRepository, $s, $this->eventsLogger);
    }

    public function testPushBasecampWebhookToQueue()
    {
        $this->givenBasecampWebhook('todo_created.json');
        $this->eventsLogger->shouldReceive('__invoke')
            ->once()
            ->with(
                Event::WEBHOOK_BASECAMP,
                [
                    'basecamp' => [
                        'event' => 'todo_created',
                        'project' => self::BASECAMP_ID,
                    ],
                ]
            );
        $this->processWebhook();
    }

    public function testIgnoreUnknownBasecampProject()
    {
        $this->givenBasecampWebhook('todo_created.json');
        $this->isBasecampProjectMappped = false;
        $this->processWebhook();
    }

    public function testIgnoreUnrelatedBasecampWebhooks()
    {
        $this->givenBasecampWebhook('message_created.json');
        $this->eventsLogger->shouldReceive('__invoke')->never();
        $this->processWebhook();
    }

    protected function processWebhook()
    {
        if ($this->isBasecampProjectMappped) {
            $this->whenProjectIsMapped(self::BASECAMP_ID);
        }
        $this->synchronize(null);
    }

    private function givenBasecampWebhook($file)
    {
        parent::givenWebhook(
            "basecamp/{$file}",
            ['user-agent'  => ['Basecamp3 Webhook']]
        );
    }
}
