<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;
use Costlocker\Integrations\Entities\Event;

class SyncWebhookToBasecampTest extends \PHPUnit_Framework_TestCase
{
    private $basecamp;
    private $database;

    private $request;

    protected function setUp()
    {
        $this->basecamp = m::mock(BasecampApi::class);
        $this->database = new InMemoryDatabase();
    }

    public function testIgnoreUnmappedProject()
    {
        $this->givenWebhook('create-activity-and-persons.json');
        $this->basecamp->shouldReceive('grantAccess')->never();
        $this->synchronize(Event::RESULT_FAILURE);
    }

    public function testConvertNewActivityAndTaskToTodolistsAndTodos()
    {
        $basecampId = 'irrelevant project';
        $this->givenWebhook('create-activity-and-persons.json');
        $this->whenProjectIsMapped($basecampId);
        $this->basecamp->shouldReceive('projectExists')->once();
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com', 2 => 'peter@example.com']));
        $this->givenBasecampTodolist($basecampId, []);
        $this->basecamp->shouldReceive('createTodolist')->once()
            ->with($basecampId, 'Development')
            ->andReturn($basecampId);
        $this->basecamp->shouldReceive('createTodo')->once()
            ->with($basecampId, $basecampId, 'Homepage', 1)
            ->andReturn($basecampId);
        $this->basecamp->shouldReceive('createTodo')->once()
            ->with($basecampId, $basecampId, 'Development', 2)
            ->andReturn($basecampId);
        $this->synchronize();
        $this->assertEquals(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            971 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => 'Homepage',
                            ],
                        ],
                        'persons' => [
                            6 => [
                                'id' => $basecampId,
                                'person_id' => 6,
                                'name' => 'Development',
                            ],
                        ],
                    ],
                ],
            ],
            $this->database->findProject(1)
        );
    }

    public function testIgnoreProjectWhereTodosSyncIsDisabled()
    {
        $basecampId = 'irrelevant project';
        $this->request['areTodosEnabled'] = false;
        $this->givenWebhook('create-activity-and-persons.json');
        $this->whenProjectIsMapped($basecampId, [], ['areTodosEnabled' => false]);
        $this->basecamp->shouldReceive('projectExists')->once();
        $this->basecamp->shouldReceive('grantAccess')->never();
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertEquals(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [],
            ],
            $this->database->findProject(1)
        );
    }

    public function testIgnoreUpdatedTaskOrActivity()
    {
        $basecampId = 'irrelevant project';
        $this->givenWebhook('update-person-and-tasks.json');
        $this->whenProjectIsMapped($basecampId, [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    971 => [
                        'id' => $basecampId,
                        'person_id' => 1,
                        'name' => 'Homepage',
                    ],
                ],
                'persons' => [
                ],
            ],
        ]);
        $this->basecamp->shouldReceive('projectExists')->once();
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com']));
        $this->givenBasecampTodolist($basecampId, []);
        $this->basecamp->shouldReceive('createTodo')->once()
            ->with($basecampId, $basecampId, 'Contact', 1)
            ->andReturn($basecampId);
        $this->synchronize();
        $this->assertEquals(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            971 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => 'Homepage',
                            ],
                            972 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => 'Contact',
                            ],
                        ],
                        'persons' => [
                        ],
                    ],
                ],
            ],
            $this->database->findProject(1)
        );
    }

    public function testIgnoreChangeInTaskName()
    {
        $basecampId = 'irrelevant project';
        $this->givenWebhook('update-task-name.json');
        $originalMapping = [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    971 => [
                        'id' => $basecampId,
                        'person_id' => 1,
                        'name' => 'Homepage',
                    ],
                ],
                'persons' => [
                ],
            ],
        ];
        $this->whenProjectIsMapped($basecampId, $originalMapping);
        $this->basecamp->shouldReceive('projectExists')->once();
        $this->basecamp->shouldReceive('grantAccess')->never(); // grantAccess only for item=person
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com']));
        $this->givenBasecampTodolist($basecampId, []);
        $this->basecamp->shouldReceive('createTodolist')->never();
        $this->basecamp->shouldReceive('createTodo')->never();
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertEquals(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => $originalMapping,
            ],
            $this->database->findProject(1)
        );
    }

    public function testDeleteTask()
    {
        $basecampId = 'irrelevant project';
        $this->givenWebhook('delete-task.json');
        $this->whenProjectIsMapped($basecampId, [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    971 => [
                        'id' => $basecampId,
                        'person_id' => 1,
                        'name' => 'Homepage',
                    ],
                    972 => [
                        'id' => $basecampId,
                        'person_id' => 1,
                        'name' => 'Contact',
                    ],
                ],
                'persons' => [
                ],
            ],
        ]);
        $this->basecamp->shouldReceive('projectExists')->once();
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com']));
        $this->givenBasecampTodolist($basecampId, [$basecampId]);
        $this->basecamp->shouldReceive('deleteTodo')->once()->with(m::any(), $basecampId);
        $this->synchronize();
        $this->assertEquals(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            971 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => 'Homepage',
                            ],
                        ],
                        'persons' => [
                        ],
                    ],
                ],
            ],
            $this->database->findProject(1)
        );
    }

    public function testDeleteActivity()
    {
        $basecampId = 'irrelevant project';
        $this->givenWebhook('delete-activity.json');
        $this->whenProjectIsMapped($basecampId, [
            1 => [
                'id' => 'deleted todolist',
                'tasks' => [
                ],
                'persons' => [
                    1 => [
                        'id' => 'first delete',
                        'person_id' => 1,
                        'name' => 'Development',
                    ],
                    6 => [
                        'id' => 'second delete',
                        'person_id' => 6,
                        'name' => 'Development',
                    ],
                ],
            ],
        ]);
        $this->basecamp->shouldReceive('projectExists')->once();
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com', 2 => 'peter@example.com']));
        $this->givenBasecampTodolist('deleted todolist', ['first delete', 'second delete']);
        $this->basecamp->shouldReceive('deleteTodolist')->once()->with(m::any(), 'deleted todolist');
        $this->basecamp->shouldReceive('deleteTodo')->once()->with(m::any(), 'first delete');
        $this->basecamp->shouldReceive('deleteTodo')->once()->with(m::any(), 'second delete');
        $this->synchronize();
        $this->assertEquals(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [],
            ],
            $this->database->findProject(1)
        );
    }

    public function testIgnoreOtherEvents()
    {
        $this->givenWebhook('create-project.json');
        $this->basecamp->shouldReceive('grantAccess')->never();
        $this->synchronize();
    }

    private function givenWebhook($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/webhooks/{$file}");
        $this->request = [
            'headers' => [], // should verify that webhook is from costlocker
            'body' => json_decode($json, true)
        ];
    }

    private function whenProjectIsMapped($basecampId, array $activities = [], array $settings = [])
    {
        $defaultSettings = new SyncRequest();
        $defaultSettings->isDeletingTodosEnabled = true;
        $this->database->upsertProject(
            1,
            [
                'id' => $basecampId,
                'activities' => $activities,
                'account' => [
                    'id' => [], // should be int id, but it's asserted in assertEquals due to legacy
                ],
                'settings' => $settings + $defaultSettings->toSettings(),
            ]
        );
    }

    private function givenBasecampPeople(array $emails)
    {
        $people = [];
        foreach ($emails as $id => $email) {
            $people[$email] = (object) [
                'id' => $id,
                'name' => $email,
                'admin' => false,
            ];
        }
        return $people;
    }

    private function givenBasecampTodolist($todolistId, $todoIds = [])
    {
        $items = [];
        foreach ($todoIds as $id) {
            $items[$id] = (object) ['assignee_id' => null];
        }
        $this->basecamp->shouldReceive('getTodolists')->once()
            ->andReturn([
                $todolistId => (object) [
                    'todoitems' => $items,
                ],
            ]);
    }

    private function synchronize($expectedStatus = Event::RESULT_SUCCESS)
    {
        $basecampFactory = m::mock(BasecampFactory::class);
        $basecampFactory->shouldReceive('__invoke')->andReturn($this->basecamp);
        $basecampFactory->shouldReceive('getAccount')->andReturn([]);
        $uc = new SyncWebhookToBasecamp($basecampFactory, $this->database);
        $results = $uc($this->request);
        if ($results) {
            assertThat($results[0]->getResultStatus(), is($expectedStatus));
        }
    }

    public function tearDown()
    {
        m::close();
    }
}
