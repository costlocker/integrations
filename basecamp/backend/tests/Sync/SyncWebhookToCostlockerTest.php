<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\Entities\Event;

class SyncWebhookToCostlockerTest extends GivenCostlockerToBasecampSynchronizer
{
    public function setUp()
    {
        parent::setUp();
        $this->request = [
            'costlockerProject' => 1,
        ];
    }

    protected function createSynchronizer(Synchronizer $s)
    {
        return new SyncProjectToCostlocker($s);
    }

    public function testPushChangesToCostlockerButNoChangeInBasecamp()
    {
        $basecampId = 'irrelevant id';
        $this->whenProjectIsMapped(
            $basecampId,
            [
                1 => [
                    'id' => $basecampId,
                    'tasks' => [],
                    'persons' => [],
                ]
            ],
            [
                'areTasksEnabled' => true,
                'isDeletingTasksEnabled' => true,
                'isCreatingActivitiesEnabled' => true,
                'isDeletingActivitiesEnabled' => true,
            ]
        );
        $this->whenProjectExistsInBasecamp();
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->basecamp->shouldReceive('canBeSynchronizedFromBasecamp')->andReturn(true);
        $this->basecamp->shouldReceive('getTodolists')->once()->andReturn([
            $basecampId => (object) [
                'name' => 'existing todolist',
                'todoitems' => [
                    'todo created in costlocker (task)' => (object) [
                        'content' => 'basecamp todo',
                        'assignee' => [
                            'email' => 'john@example.com',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                        ],
                    ],
                ],
            ],
        ]);
        $this->costlocker->shouldReceive('__invoke')
            ->with('/projects', m::on(function ($data) {
                assertThat($data['items'], is(arrayWithSize(1)));
                return true;
            }))
            ->andReturn(
                new Response(200, [], json_encode([
                    'data' => [
                        [
                            'items' => [
                                [
                                    'action' => 'upsert',
                                    'item' => [
                                        'type' => 'task',
                                        'activity_id' => 1,
                                        'person_id' => 1,
                                        'task_id' => 123,
                                    ],
                                ],
                            ],  
                        ],
                    ],
                ]))
            );
        $this->synchronize(Event::RESULT_SUCCESS);
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'account' => null,
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            123 => [
                                'id' => 'todo created in costlocker (task)',
                                'person_id' => 1,
                                'name' => 'basecamp todo',
                            ],
                        ],
                        'persons' => [],
                    ],
                ],
            ]
        );
    }

    public function testIgnoreUnknownBasecampProject()
    {
        $this->synchronize(null);
    }
}
