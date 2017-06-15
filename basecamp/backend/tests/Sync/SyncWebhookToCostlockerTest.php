<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Database\CompaniesRepository;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class SyncWebhookToCostlockerTest extends GivenCostlockerToBasecampSynchronizer
{
    private $eventsLogger;

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

    public function testLogBasecampEvent()
    {
        $this->givenBasecampWebhook('todo_created.json');
        $this->eventsLogger->shouldReceive('__invoke')
            ->once()
            ->with(
                Event::WEBHOOK_BASECAMP,
                [
                    'basecamp' => [
                        'event' => 'todo_created',
                        'project' => 3929343,
                    ],
                ]
            );
        $this->synchronize(null);
    }

    public function testIgnoreUnrelatedBasecampWebhooks()
    {
        $this->givenBasecampWebhook('message_created.json');
        $this->eventsLogger->shouldReceive('__invoke')->never();
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
