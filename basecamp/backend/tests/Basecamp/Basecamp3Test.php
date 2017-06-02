<?php

namespace Costlocker\Integrations\Basecamp\Api;

class Basecamp3Test extends \Tests\Basecamp\GivenBasecampConnect
{
    private $todoset;

    protected function getApiType()
    {
        return Connect::BASECAMP_V3_TYPE;
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

    public function testGetCompanies()
    {
        assertThat($this->api->getCompanies(), is(emptyArray()));
        $this->assertApiWasNotCalled();
    }

    public function testGetPeople()
    {
        parent::testGetPeople();
        $this->assertCalledUrl("/projects/{$this->project}/people.json");
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

    public function testArchiveProject()
    {
        $this->whenEntityIsDeleted();
        $this->api->archiveProject($this->project);
        $this->assertCalledUrl("/projects/{$this->project}.json");
    }

    public function testGetTodolists()
    {
        $this->givenTodoset();
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
            "/projects/{$this->project}.json",
            "/buckets/{$this->project}/todosets/{$this->todoset}/todolists.json",
            "/buckets/{$this->project}/todolists/1/todos.json"
        );
    }

    public function testCreateTodolist()
    {
        $this->givenTodoset();
        $this->whenEntityIsCreated(
            function () {
                return $this->api->createTodolist($this->project, 'name');
            }
        );
        $this->assertCalledUrl(
            "/projects/{$this->project}.json",
            "/buckets/{$this->project}/todosets/{$this->todoset}/todolists.json"
        );
        $this->assertRequestContains('{"name":"name"}');
    }

    public function testTodosetIsCachedInMemory()
    {
        $this->givenTodoset();
        $createTodolist = function () {
            return $this->api->createTodolist($this->project, 'Nth list');
        };
        $this->whenEntityIsCreated($createTodolist);
        $this->whenEntityIsCreated($createTodolist);
        $this->assertCalledUrl(
            "/projects/{$this->project}.json",
            "/buckets/{$this->project}/todosets/{$this->todoset}/todolists.json",
            "/buckets/{$this->project}/todosets/{$this->todoset}/todolists.json"
        );
    }

    private function givenTodoset()
    {
        $this->todoset = 9007199254741445;
        $this->whenApiReturns('project');
    }

    public function testDeleteTodolist()
    {
        $this->whenEntityIsDeleted();
        $this->api->deleteTodolist($this->project, $this->id);
        $this->assertCalledUrl("/buckets/{$this->project}/todolists/{$this->id}.json");
    }

    /** @dataProvider provideTodoSettings */
    public function testCreateTodo($content, $assignee, $expectedTodo)
    {
        $this->whenEntityIsCreated(
            function () use ($content, $assignee) {
                return $this->api->createTodo($this->project, $this->id, $content, $assignee);
            }
        );
        $this->assertCalledUrl("/buckets/{$this->project}/todolists/{$this->id}/todos.json");
        $this->assertRequestContains("{{$expectedTodo}}");
    }

    public function provideTodoSettings()
    {
        return [
            'basic todo' => ['todo', null, '"content":"todo"'],
            'todo with assignee' => ['todo', '1', '"content":"todo","assignee_ids":[1]'],
        ];
    }

    public function testCompleteTodoitem()
    {
        $this->whenEntitiesAreModified();
        $this->api->completeTodo($this->project, $this->id);
        $this->assertCalledUrl("/buckets/{$this->project}/todos/{$this->id}/completion.json");
    }

    public function testDeleteTodoitem()
    {
        $this->whenEntityIsDeleted();
        $this->api->deleteTodo($this->project, $this->id);
        $this->assertCalledUrl("/buckets/{$this->project}/todos/{$this->id}.json");
    }

    public function testGrantAccess()
    {
        $personId = 123;
        $this->whenEntityIsUpdated('grant');
        $this->api->grantAccess($this->project, ['name' => 'mail', $personId]);
        $this->assertCalledUrl("/projects/{$this->project}/people/users.json");
        $this->assertRequestContains('{"grant":[' . $personId . '],"revoke":[],"create":[{"name":"name","email_address":"mail"}]}');
    }

    public function testRevokeAccess()
    {
        $this->whenEntityIsUpdated();
        $this->api->revokeAccess($this->project, $this->id);
        $this->assertCalledUrl("/projects/{$this->project}/people/users.json");
        $this->assertRequestContains('{"revoke":["' . $this->id . '"]}');
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
            is("https://3.basecamp.com/account/projects/{$this->project}")
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
