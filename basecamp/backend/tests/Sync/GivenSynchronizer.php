<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampAdapter;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Events\EventsLogger;

abstract class GivenSynchronizer extends \PHPUnit_Framework_TestCase
{
    const WEBHOOK_URL = '';

    protected $costlocker;
    protected $basecamp;
    protected $database;
    protected $eventsLogger;
    protected $hasWebhookValidSignature = true;

    protected $eventType;
    protected $request;

    public function setUp()
    {
        $this->costlocker = m::mock(CostlockerClient::class);
        $this->basecamp = m::mock(BasecampApi::class);
        $this->database = new InMemoryDatabase();
        $this->eventsLogger = m::mock(EventsLogger::class);
    }

    protected function givenCostlockerProject($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/{$file}");
        $this->costlocker->shouldReceive('__invoke')
            ->with(m::on(function ($path) {
                return is_int(strpos($path, '/projects/'));
            }))
            ->andReturn(new Response(200, [], $json));
    }

    protected function givenWebhook($file, $headers)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/webhooks/{$file}");
        $this->request = [
            'headers' => $headers,
            'rawBody' => $json,
        ];
    }

    protected function whenProjectIsMapped($basecampId, array $activities = [], array $settings = [])
    {
        $result = new SyncResponse(new SyncRequest());
        $result->costlockerChangelog->projectId = 1;
        $result->basecampChangelog->projectId = $basecampId;
        $result->request->settings = new SyncSettings($settings + ['isDeletingTodosEnabled' => true]);
        $result->newMapping = $activities;
        $this->database->upsertProject($result);
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
        $this->basecamp->shouldReceive('getTodolists')
            ->atMost()->once()
            ->andReturn($basecamp);
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

        $this->basecamp->shouldReceive('getPeople')
            ->atMost()->once()
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

        $basecamps = m::mock(BasecampAdapter::class);
        $basecamps->shouldReceive('buildClient')->andReturn($this->basecamp);

        $verifier = m::mock(CostlockerWebhookVerifier::class);
        $verifier->shouldReceive('__invoke')->andReturn($this->hasWebhookValidSignature);

        $event = new Event();
        $event->data = [
            'type' => $this->eventType,
            'webhookUrl' => '',
            'request' => $this->request,
        ];

        $uc = new ProcessEvent([
            'client.costlocker' => $this->costlocker,
            'client.user' => $user,
            'client.basecamp' => $basecamps,
            'signature.costlocker' => $verifier,
            'database' => $this->database,
            'events.logger' => $this->eventsLogger,
        ]);
        $results = $uc($event, null, self::WEBHOOK_URL);
        if (is_string($expectedStatus)) {
            assertThat($results, containsString($expectedStatus));
        } elseif ($results) {
            assertThat($results[0]->getResultStatus(), is($expectedStatus));
        } elseif ($expectedStatus) {
            $this->fail("One project should be synchronized with status '{$expectedStatus}'");
        }
    }

    protected function assertNoMappingInDatabase($basecampId)
    {
        $this->assertMappingIs([
            'id' => $basecampId,
            'activities' => [],
        ]);
    }

    protected function assertMappingIsNotEmpty()
    {
        $project = $this->database->findByCostlockerId(1);
        assertThat($project, anInstanceOf(\Costlocker\Integrations\Entities\BasecampProject::class));
    }

    protected function assertMappingIs(array $expectedMapping)
    {
        $project = $this->database->findByCostlockerId(1);
        assertThat($project->basecampProject, is($expectedMapping['id']));
        $this->assertEquals($expectedMapping['activities'], $project->mapping); // prettier diff on failure
        assertThat($project->mapping, is($expectedMapping['activities']));
    }

    public function tearDown()
    {
        m::close();
    }
}
