<?php

namespace Costlocker\Integrations\Basecamp;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;

class SyncProjectTest extends \PHPUnit_Framework_TestCase
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
        $this->request = new SyncRequest();
        $this->request->account = 'irrelevant basecamp account';
        $this->request->costlockerProject = 'irrelevant id';
    }

    /** @dataProvider provideCreate */
    public function testCreateProject($updatedBasecampProject, $basecampId)
    {
        $this->request->updatedBasecampProject = $updatedBasecampProject;
        $this->givenCostlockerProject('one-person.json');
        $this->basecamp->shouldReceive('createProject')
            ->times($updatedBasecampProject ? 0 : 1)
            ->with('ACME | Website', null, null)
            ->andReturn($basecampId);
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
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => $basecampId,
                        ],
                        'persons' => [
                            885 => $basecampId,
                        ],
                    ]
                ],
            ],
            $this->database->findProject(1)
        );
    }

    public function provideCreate()
    {
        return [
            'create new project' => [null, 'irrelevant new id'],
            'add to existing project' => ['irrelevant existing id', 'irrelevant existing id'],
        ];
    }

    public function testPartialUpdate()
    {
        $basecampId = 'existing id';
        $this->database->upsertProject(
            1,
            [
                'id' => $basecampId,
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => $basecampId,
                        ],
                        'persons' => [
                        ],
                    ],
                ],
            ]
        );
        $this->givenCostlockerProject('one-person.json');
        $this->basecamp->shouldReceive('createProject')->never();
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com', 2 => 'peter@example.com']));
        $this->basecamp->shouldReceive('createTodolist')->never();
        $this->basecamp->shouldReceive('createTodo')->once()->andReturn('new id');
        $this->synchronize();
        $this->assertEquals(
            [
                'id' => $basecampId,
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => $basecampId,
                        ],
                        'persons' => [
                            885 => 'new id',
                        ],
                    ]
                ],
            ],
            $this->database->findProject(1)
        );
    }

    public function testPartialDelete()
    {
        $basecampId = 'irrelevant project';
        $this->database->upsertProject(
            1,
            [
                'id' => $basecampId,
                'activities' => [
                    1 => [
                        'id' => 'non-empty todolist',
                        'tasks' => [
                            885 => 'existing todo',
                        ],
                        'persons' => [
                            885 => 'unknown basecamp id',
                        ],
                    ],
                    2 => [
                        'id' => 'empty todolist',
                        'tasks' => [],
                        'persons' => [],
                    ],
                    3 => [
                        'id' => 'todolist not in BC',
                        'tasks' => [],
                        'persons' => [],
                    ],
                ],
            ]
        );
        $this->request->isDeletingTodosEnabled = true;
        $this->request->isRevokeAccessEnabled = true;
        $this->givenCostlockerProject('empty-project.json');
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com', 2 => 'peter@example.com']));
        $this->basecamp->shouldReceive('getTodolists')->once()
            ->andReturn([
                'non-empty todolist' => (object) [
                    'todoitems' => [
                        'existing todo' => (object) ['assignee_id' => null],
                        'todo manually created in BC (non-empty todolist is not deleted)' =>
                            (object) ['assignee_id' => 2],
                    ],
                ],
                'empty todolist' => (object) [
                    'todoitems' => [],
                ],
            ]);
        $this->basecamp->shouldReceive('deleteTodolist')->once()->with(m::any(), 'empty todolist');
        $this->basecamp->shouldReceive('deleteTodo')->once()->with(m::any(), 'existing todo');
        $this->basecamp->shouldReceive('revokeAccess')->once();
        $this->synchronize();
        $this->assertEquals(
            [
                'id' => $basecampId,
                'activities' => [
                    // not deleted because todolist is not empty in BC
                    1 => [
                        'id' => 'non-empty todolist',
                        'tasks' => [],
                        'persons' => [],
                    ]
                ],
            ],
            $this->database->findProject(1)
        );
    }

    private function givenCostlockerProject($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/{$file}");
        $this->costlocker->shouldReceive('__invoke')->andReturn(new Response(200, [], $json));
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
        $uc = new SyncProject($this->costlocker, $basecampFactory, $this->database);
        $uc($this->request);
    }

    public function tearDown()
    {
        m::close();
    }
}
