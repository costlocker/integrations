<?php

namespace Costlocker\Integrations\Basecamp;

use Mockery as m;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;

class SyncWebhookToBasecampTest extends \PHPUnit_Framework_TestCase
{
    private $costlocker;
    private $basecamp;
    private $database;

    private $request;

    protected function setUp()
    {
        $this->costlocker = m::mock(CostlockerClient::class);
        $this->basecamp = m::mock(BasecampApi::class);
        $this->database = new InMemoryDatabase();
    }

    public function testIgnoreUnmappedProject()
    {
        $this->givenWebhook('create-activity-and-persons.json');
        $this->basecamp->shouldReceive('grantAccess')->never();
        $this->synchronize();
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
                            971 => $basecampId,
                        ],
                        'persons' => [
                            6 => $basecampId,
                        ],
                    ],
                ],
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
                    971 => $basecampId,
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
                            971 => $basecampId,
                            972 => $basecampId,
                        ],
                        'persons' => [
                        ],
                    ],
                ],
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
                    971 => $basecampId,
                    972 => $basecampId,
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
                            971 => $basecampId,
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
                    1 => 'first delete',
                    6 => 'second delete',
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
        $this->request = file_get_contents(__DIR__ . "/fixtures/webhooks/{$file}");
    }

    private function whenProjectIsMapped($basecampId, array $activities = [])
    {
        $this->database->upsertProject(
            1,
            [
                'id' => $basecampId,
                'activities' => $activities,
                'account' => [],
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

    private function synchronize()
    {
        $basecampFactory = m::mock(BasecampFactory::class);
        $basecampFactory->shouldReceive('__invoke')->andReturn($this->basecamp);
        $basecampFactory->shouldReceive('getAccount')->andReturn([]);
        $uc = new SyncWebhookToBasecamp($this->costlocker, $basecampFactory, $this->database);
        $uc($this->request);
    }

    public function tearDown()
    {
        m::close();
    }
}