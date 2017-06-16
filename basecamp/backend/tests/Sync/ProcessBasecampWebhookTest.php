<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Database\CompaniesRepository;
use Costlocker\Integrations\Entities\Event;

class ProcessBasecampWebhookTest extends GivenCostlockerToBasecampSynchronizer
{
    const BASECAMP_ID = 123456;

    private $isBasecampProjectMappped = true;
    private $isBasecampSynchronizationAllowed = true;
    
    protected function createSynchronizer(Synchronizer $s)
    {
        $companiesRepository = m::mock(CompaniesRepository::class);
        return new SyncWebhookToBasecamp($companiesRepository, $this->database, $s, $this->eventsLogger);
    }

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
