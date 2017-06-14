<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Entities\Event;
use GuzzleHttp\Psr7\Response;

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

    public function testNoChangeInBasecamp()
    {
        $basecampId = 'irrelevant project';
        $originalMapping = [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    885 => [
                        'id' => 'todo created in costlocker (task)',
                        'person_id' => 1,
                        'name' => 'task todo',
                    ],
                ],
                'persons' => [
                    885 => [
                        'id' => 'todo created in costlocker (person)',
                        'person_id' => 885,
                        'name' => 'person todo',
                    ],
                ],
            ],
        ];
        $this->whenProjectIsMapped($basecampId, $originalMapping);
        $this->givenCostlockerProject('one-person.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->basecamp->shouldReceive('canBeSynchronizedFromBasecamp')->andReturn(true);
        $this->basecamp->shouldReceive('getTodolists')->once()->andReturn([
            $basecampId => (object) [
                'todoitems' => [
                    'todo created in costlocker (task)' => (object) [
                        'content' => 'task todo',
                        'assignee' => [
                            'email' => 'john@example.com',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                        ],
                    ],
                    'todo created in costlocker (person)' => (object) [
                        'content' => 'person todo',
                        'assignee' => [
                            'email' => 'peter@example.com',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                        ],
                    ],
                ],
            ],
        ]);
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => $originalMapping,
            ]
        );
    }

    public function testAddTaskToCostlocker()
    {
        $basecampId = 'irrelevant project';
        $this->whenProjectIsMapped($basecampId, [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    885 => [
                        'id' => 'todo created in costlocker (task)',
                        'person_id' => 1,
                        'name' => 'task todo',
                    ],
                ],
                'persons' => [
                    885 => [
                        'id' => 'todo created in costlocker (person)',
                        'person_id' => 885,
                        'name' => 'person todo',
                    ],
                ],
            ],
        ]);
        $this->givenCostlockerProject('one-person.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->basecamp->shouldReceive('canBeSynchronizedFromBasecamp')->andReturn(true);
        $this->basecamp->shouldReceive('getTodolists')->once()->andReturn([
            $basecampId => (object) [
                'todoitems' => [
                    'todo created in costlocker (task)' => (object) [
                        'content' => 'task todo',
                        'assignee' => [
                            'email' => 'john@example.com',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                        ],
                    ],
                    'todo created in costlocker (person)' => (object) [
                        'content' => 'person todo',
                        'assignee' => [
                            'email' => 'peter@example.com',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                        ],
                    ],
                    'new todo in basecamp (task)' => (object) [
                        'content' => 'basecamp todo',
                        'assignee' => [
                            'email' => 'john@example.com',
                            'first_name' => 'John',
                            'last_name' => 'Doe',
                        ],
                    ],
                    'new todo in basecamp (person)' => (object) [
                        'content' => 'basecamp todo2',
                        'assignee' => [
                            'email' => 'peter@example.com',
                            'first_name' => 'Peter',
                            'last_name' => 'Nobody',
                        ],
                    ],
                    'ignore todo without assignee' => (object) [
                        'content' => 'unassigned todo',
                        'assignee' => null,
                    ],
                ],
            ],
        ]);
        $this->costlocker->shouldReceive('__invoke')
            ->with('/projects', m::on(function ($data) {
                assertThat($data['items'], is(arrayWithSize(2)));
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
                                [
                                    'action' => 'upsert',
                                    'item' => [
                                        'type' => 'task',
                                        'activity_id' => 1,
                                        'person_id' => 885,
                                        'task_id' => 456,
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
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => [
                                'id' => 'todo created in costlocker (task)',
                                'person_id' => 1,
                                'name' => 'task todo',
                            ],
                            123 => [
                                'id' => 'new todo in basecamp (task)',
                                'person_id' => 1,
                                'name' => 'basecamp todo',
                            ],
                            456 => [
                                'id' => 'new todo in basecamp (person)',
                                'person_id' => 885,
                                'name' => 'basecamp todo2',
                            ],
                        ],
                        'persons' => [
                            // person task is deleted in Costlocker
                            // but person todo (with activity name) remains in Basecamp
                            // manual fix: delete task in BC or run full sync (task is imported to CL)
                        ],
                    ],
                ],
            ]
        );
    }

    public function testDeleteTaskInCostlocker()
    {
        $basecampId = 'irrelevant project';
        $this->request['isDeletingTasksEnabled'] = true;
        $originalMapping = [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    885 => [
                        'id' => 'todo created in costlocker (task)',
                        'person_id' => 1,
                        'name' => 'task todo',
                    ],
                ],
                'persons' => [
                    885 => [
                        'id' => 'todo created in costlocker (person)',
                        'person_id' => 885,
                        'name' => 'person todo',
                    ],
                ],
            ],
        ];
        $this->whenProjectIsMapped($basecampId, $originalMapping);
        $this->givenCostlockerProject('one-person.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->basecamp->shouldReceive('canBeSynchronizedFromBasecamp')->andReturn(true);
        $this->basecamp->shouldReceive('getTodolists')->once()->andReturn([
            $basecampId => (object) [
                'todoitems' => [],
            ],
        ]);
        $this->costlocker->shouldReceive('__invoke')
            ->with('/projects', m::on(function ($data) {
                assertThat($data['items'], is(arrayWithSize(2)));
                return true;
            }))
            ->andReturn(
                new Response(200, [], json_encode([
                    'data' => [
                        [
                            'items' => [
                                [
                                    'action' => 'delete',
                                    'item' => [
                                        'type' => 'task',
                                        'activity_id' => 1,
                                        'person_id' => 1,
                                        'task_id' => 885,
                                    ],
                                ],
                                [
                                    'action' => 'delete',
                                    'item' => [
                                        'type' => 'person',
                                        'activity_id' => 1,
                                        'person_id' => 885,
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
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [],
                        'persons' => [],
                    ],
                ],
            ]
        );
    }

    public function testIgnoreFailedCostlocker()
    {
        $basecampId = 'irrelevant project';
        $this->request['isDeletingTasksEnabled'] = true;
        $originalMapping = [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    885 => [
                        'id' => 'todo created in costlocker (task)',
                        'person_id' => 1,
                        'name' => 'task todo',
                    ],
                ],
                'persons' => [
                    885 => [
                        'id' => 'todo created in costlocker (person)',
                        'person_id' => 885,
                        'name' => 'person todo',
                    ],
                ],
            ],
        ];
        $this->whenProjectIsMapped($basecampId, $originalMapping);
        $this->givenCostlockerProject('one-person.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->basecamp->shouldReceive('canBeSynchronizedFromBasecamp')->andReturn(true);
        $this->basecamp->shouldReceive('getTodolists')->once()->andReturn([
            $basecampId => (object) [
                'todoitems' => [],
            ],
        ]);
        $this->costlocker->shouldReceive('__invoke')
            ->with('/projects', m::any())
            ->andReturn(new Response(400));
        $this->synchronize(Event::RESULT_FAILURE);
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => $originalMapping,
            ]
        );
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
