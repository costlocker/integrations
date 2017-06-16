<?php

namespace Costlocker\Integrations\Basecamp\Api;

class BasecampBcxTest extends \Tests\Basecamp\GivenBasecampConnect
{
    protected function getApiType()
    {
        return Connect::BASECAMP_BCX_TYPE;
    }

    protected function getResponseType()
    {
        return 'json';
    }

    protected function getCreateResponseWithId($id)
    {
        return [
            'body' => json_encode([
                'id' => $id,
            ]),
        ];
    }

    public function testSynchronizationFromBasecamp()
    {
        assertThat($this->api->canBeSynchronizedFromBasecamp(), is(false));
    }

    public function testRegisterWebhook()
    {
        $this->setExpectedException(BasecampInvalidCallException::class);
        $this->api->registerWebhook('123', 'irrelevant url');
    }

    public function testGetCompanies()
    {
        assertThat($this->api->getCompanies(), is(emptyArray()));
        $this->assertApiWasNotCalled();
    }

    public function testGetTodolists()
    {
        $this->whenApiReturns('todolists', 'todoitem');
        $this->assertEquals(
            $this->api->getTodolists($this->project),
            [
                1 => (object) [
                    'name' => 'Development',
                    'todoitems' => [
                        1 => (object) [
                            'content' => 'todo',
                            'creator_id' => 1,
                            'creator_name' => 'John Doe',
                            'assignee_id' => 149087659,
                            'assignee_name' => 'Jason Fried',
                        ]
                    ]
                ]
            ]
        );
        $this->assertCalledUrl(
            "/projects/{$this->project}/todolists.json",
            "/projects/{$this->project}/todolists/1.json"
        );
    }

    public function testGetPeople()
    {
        parent::testGetPeople();
        $this->assertCalledUrl("/projects/{$this->project}/accesses.json");
    }

    /** @dataProvider provideProjectSettings */
    public function testCreateProject($description, $expectedProject)
    {
        $this->whenEntityIsCreated(
            function () use ($description) {
                return $this->api->createProject($this->project, null, $description);
            }
        );
        $this->assertCalledUrl("/projects.json");
        $this->assertRequestContains($expectedProject);
    }

    public function provideProjectSettings()
    {
        return [
            'basic project' => [null, '{"name":"' . $this->project . '"}'],
            'project in company' => ['text', '{"name":"' . $this->project . '","description":"text"}'],
        ];
    }

    public function testCreateTodolist()
    {
        $this->whenEntityIsCreated(
            function () {
                return $this->api->createTodolist('projectId', 'name');
            }
        );
        $this->assertCalledUrl("/projects/projectId/todolists.json");
        $this->assertRequestContains('{"name":"name"}');
    }

    /** @dataProvider provideTodoSettings */
    public function testCreateTodo($content, $assignee, $expectedTodo)
    {
        $this->whenEntityIsCreated(
            function () use ($content, $assignee) {
                return $this->api->createTodo($this->project, $this->id, $content, $assignee);
            }
        );
        $this->assertCalledUrl("/projects/{$this->project}/todolists/{$this->id}/todos.json");
        $this->assertRequestContains("{{$expectedTodo}}");
    }

    public function provideTodoSettings()
    {
        return [
            'basic todo' => ['todo', null, '"content":"todo"'],
            'todo with assignee' => ['todo', '1', '"content":"todo","assignee":{"id":1,"type":"Person"}'],
        ];
    }

    public function testArchiveProject()
    {
        $this->whenEntityIsUpdated();
        $this->api->archiveProject($this->project);
        $this->assertRequestContains('{"archived":true}');
        $this->assertCalledUrl("/projects/{$this->project}.json");
    }

    public function testCompleteTodoitem()
    {
        $this->whenEntityIsUpdated();
        $this->api->completeTodo($this->project, $this->id);
        $this->assertRequestContains('{"completed":true}');
        $this->assertCalledUrl("/projects/{$this->project}/todos/{$this->id}.json");
    }

    public function testDeleteTodoitem()
    {
        $this->whenEntityIsDeleted();
        $this->api->deleteTodo($this->project, $this->id);
        $this->assertCalledUrl("/projects/{$this->project}/todos/{$this->id}.json");
    }

    public function testDeleteTodolist()
    {
        $this->whenEntityIsDeleted();
        $this->api->deleteTodolist($this->project, $this->id);
        $this->assertCalledUrl("/projects/{$this->project}/todolists/{$this->id}.json");
    }

    public function testGrantAccess()
    {
        $this->whenEntitiesAreModified();
        $this->api->grantAccess($this->project, ['mail']);
        $this->assertCalledUrl("/projects/{$this->project}/accesses.json");
        $this->assertRequestContains('{"email_addresses":["mail"]}');
    }

    public function testRevokeAccess()
    {
        $this->whenEntityIsDeleted();
        $this->api->revokeAccess($this->project, $this->id);
        $this->assertCalledUrl("/projects/{$this->project}/accesses/{$this->id}.json");
    }

    public function testBuildProjectUrl()
    {
        assertThat(
            $this->api->buildProjectUrl(
                (object) [
                    'bc__product_type' => $this->getApiType(),
                    'bc__account_id' => 'account',
                ],
                $this->project
            ),
            is("https://basecamp.com/account/projects/{$this->project}")
        );
    }

    public function testThrowExceptionWhenIdOfCreatedEntityIsMissing()
    {
        $this->setExpectedException(BasecampMissingReturnValueException::class);
        $this->whenEntityIsCreated(
            function () {
                return $this->api->createProject($this->project);
            },
            ['body' => '["json response without id"]']
        );
    }

    public function testThrowExceptionWhenEndpointReturnsInvalidContent()
    {
        $this->whenApiReturns('invalid');
        $this->setExpectedException(JsonException::class);
        $this->api->getProjects();
    }
}
