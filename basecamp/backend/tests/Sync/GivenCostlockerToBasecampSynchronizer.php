<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Auth\GetUser;

abstract class GivenCostlockerToBasecampSynchronizer extends \PHPUnit_Framework_TestCase
{
    protected $costlocker;
    protected $basecamp;
    protected $database;

    protected $request;

    public function setUp()
    {
        $this->costlocker = m::mock(CostlockerClient::class);
        $this->basecamp = m::mock(BasecampApi::class);
        $this->database = new InMemoryDatabase();
    }

    protected function givenCostlockerProject($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/{$file}");
        $this->costlocker->shouldReceive('__invoke')
            ->with(m::type('string'))
            ->andReturn(new Response(200, [], $json));
    }

    protected function givenCostlockerWebhook($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/webhooks/{$file}");
        $this->request = [
            'headers' => [], // should verify that webhook is from costlocker
            'body' => json_decode($json, true)
        ];
    }

    protected function whenProjectIsMapped($basecampId, array $activities = [], array $settings = [])
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

    protected function givenBasecampPeople(array $emails)
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

    protected function givenBasecampTodolists(array $todolists)
    {
        $basecamp = [];
        foreach ($todolists as $name => $todoIds) {
            $items = [];
            foreach ($todoIds as $key => $value) {
                if (is_int($key)) {
                    $items[$value] = (object) ['assignee_id' => null];
                } else {
                    $items[$key] = (object) ['assignee_id' => $value];
                }
            }
            $basecamp[$name] = (object) [
                'todoitems' => $items,
            ];
        }
        $this->basecamp->shouldReceive('getTodolists')->once()->andReturn($basecamp);
    }

    protected function shouldCreateProject()
    {
        return $this->basecamp->shouldReceive('createProject');
    }

    protected function shouldLoadBasecampPeople(array $people, $basecampId = null, $isExistingProject = true)
    {
        $basecampPeople = [];
        $id = 1;
        foreach ($people as $email) {
            $basecampPeople[$id++] = $email;
        }

        if ($basecampId) {
            $this->basecamp->shouldReceive('grantAccess')->once()
                ->with($basecampId, $people);
        } else {
            $this->basecamp->shouldReceive('grantAccess')->never();
        }

        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople($basecampPeople));
        if ($isExistingProject) {
            $this->whenProjectExistsInBasecamp();
        }
    }

    protected function whenProjectExistsInBasecamp()
    {
        return $this->basecamp->shouldReceive('projectExists')->once();
    }

    protected function shouldCreateTodos(array $todolists, $basecampId)
    {
        foreach ($todolists as $name => $todos) {
            $this->basecamp->shouldReceive('createTodolist')->once()
                ->with($basecampId, $name)
                ->andReturn($basecampId);
            foreach ($todos as $todo => $assignee) {
                $this->shouldCreateTodo()
                    ->with($basecampId, $basecampId, $todo, $assignee)
                    ->andReturn($basecampId);
            }
        }
    }

    protected function shouldCreateTodo()
    {
        return $this->basecamp->shouldReceive('createTodo')->once();
    }

    protected function shouldDeleteTodos(array $todolists)
    {
        foreach ($todolists as $name => $todos) {
            if (is_string($name)) {
                $this->basecamp->shouldReceive('deleteTodolist')->once()->with(m::any(), $name);
            }
            foreach ($todos as $todo) {
                $this->basecamp->shouldReceive('deleteTodo')->once()->with(m::any(), $todo);
            }
        }
    }

    protected function shouldRevokeAccessToOnePerson()
    {
        $this->basecamp->shouldReceive('revokeAccess')->once();
    }

    protected function shouldNotCreatePeopleOrTodosInBasecamp()
    {
        $this->basecamp->shouldReceive('grantAccess')->never();
        $this->basecamp->shouldReceive('createTodolist')->never();
        $this->basecamp->shouldReceive('createTodo')->never();
    }

    protected function synchronize($expectedStatus = Event::RESULT_SUCCESS)
    {
        $user = m::mock(GetUser::class);
        $user->shouldReceive('overrideCostlockerUser');

        $basecampFactory = m::mock(BasecampFactory::class);
        $basecampFactory->shouldReceive('__invoke')->andReturn($this->basecamp);
        $basecampFactory->shouldReceive('getAccount')->andReturn([]);

        $synchronizer = new Synchronizer($this->costlocker, $user, $basecampFactory, $this->database);
        $uc = $this->createSynchronizer($synchronizer);
        $results = $uc($this->request);
        if ($results) {
            assertThat($results[0]->getResultStatus(), is($expectedStatus));
        } elseif ($expectedStatus) {
            $this->fail("One project should be synchronized with status '{$expectedStatus}'");
        }
    }

    protected function assertNoMappingInDatabase($basecampId)
    {
        $this->assertMappingIs([
            'id' => $basecampId,
            'account' => [],
            'activities' => [],
        ]);
    }

    protected function assertMappingIsNotEmpty()
    {
        assertThat($this->database->findProject(1), is(nonEmptyArray()));
    }

    protected function assertMappingIs(array $expectedMapping)
    {
        $this->assertEquals(
            $expectedMapping,
            $this->database->findProject(1)
        );
    }

    abstract protected function createSynchronizer(Synchronizer $s);

    public function tearDown()
    {
        m::close();
    }
}
