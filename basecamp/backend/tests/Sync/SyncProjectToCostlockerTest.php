<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\Event;

class SyncProjectToCostlockerTest extends GivenCostlockerToBasecampSynchronizer
{
    public function setUp()
    {
        parent::setUp();
        $this->request = [
            // mapping
            'account' => [], // should be int id, but it's asserted in assertEquals due to legacy
            'costlockerProject' => 'irrelevant id',
            // costlocker -> basecamp sync
            'areTodosEnabled' => true,
            'isDeletingTodosEnabled' => false,
            'isRevokeAccessEnabled' => false,
            // basecamp -> costlocker
            'areTasksEnabled' => true,
            'isDeletingTasksEnabled' => false,
            'isBasecampWebhookEnabled' => false,
        ];
    }

    protected function createSynchronizer(Synchronizer $s)
    {
        return new SyncProjectToBasecamp($s);
    }

    public function testIgnoreOlderVersionOfBasecamp()
    {
        $this->request = [
            'areTasksEnabled' => true,
            'isDeletingTasksEnabled' => true,
            'isBasecampWebhookEnabled' => true,
        ] + $this->request;
        $this->whenProjectIsMapped('irrelevant basecamp project id', []);
        $this->givenCostlockerProject('empty-project.json');
        $this->shouldLoadBasecampPeople([]);
        $this->basecamp->shouldReceive('canBeSynchronizedFromBasecamp')->andReturn(false);
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertEquals(
            [
                'areTodosEnabled' => true,
                'isDeletingTodosEnabled' => false,
                'isRevokeAccessEnabled' => false,
                'areTasksEnabled' => false,
                'isDeletingTasksEnabled' => false,
                'isBasecampWebhookEnabled' => false,
            ],
            $this->database->lastSettings
        );
    }
}
