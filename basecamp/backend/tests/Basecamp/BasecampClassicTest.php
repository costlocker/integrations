<?php

namespace Costlocker\Integrations\Basecamp\Api;

class BasecampClassicTest extends \Tests\Basecamp\GivenBasecampConnect
{
    protected function getApiType()
    {
        return Connect::BASECAMP_CLASSIC_TYPE;
    }

    protected function getResponseType()
    {
        return 'xml';
    }

    protected function getCreateResponseWithId($id)
    {
        return [
            'header' => <<<HEADERS
                Server: nginx
                Date: Sun, 12 Jan 2014 12:19:50 GMT
                Location: /url/{$id}.xml
HEADERS
        ];
    }

    public function testGetCompanies()
    {
        $this->whenApiReturns('companies');
        assertThat($this->api->getCompanies(), is([
            (object) [
                'id' => 1,
                'name' => 'Globex Corporation'
            ]
        ]));
        $this->assertCalledUrl("/companies.xml");
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
                            'assignee_id' => null,
                            'assignee_name' => null,
                        ]
                    ]
                ]
            ]
        );
        $this->assertCalledUrl(
            "/projects/{$this->project}/todo_lists.xml",
            "/todo_lists/1/todo_items.xml"
        );
    }

    public function testGetPeople()
    {
        parent::testGetPeople();
        $this->assertCalledUrl("/projects/{$this->project}/people.xml");
    }

    /** @dataProvider provideProjectSettings */
    public function testCreateProject($company, $expectedProject)
    {
        $this->whenEntityIsCreated(
            function () use ($company) {
                return $this->api->createProject($this->project, $company);
            }
        );
        $this->assertCalledUrl("/projects.xml");
        $this->assertRequestContains("<request><project>{$expectedProject}</project></request>");
    }

    public function provideProjectSettings()
    {
        return [
            'basic project' => [null, "<name>{$this->project}</name>"],
            'project in company' => [123, "<name>{$this->project}</name><company-id>123</company-id>"],
        ];
    }

    public function testCreateTodolist()
    {
        $this->whenEntityIsCreated(
            function () {
                return $this->api->createTodolist($this->project, 'name');
            }
        );
        $this->assertCalledUrl("/projects/{$this->project}/todo_lists.xml");
        $this->assertRequestContains("<todo-list><name>name</name></todo-list>");
    }

    public function testCreateTodo()
    {
        $this->whenEntityIsCreated(
            function () {
                return $this->api->createTodo($this->project, $this->id, 'todo');
            }
        );
        $this->assertCalledUrl("/todo_lists/{$this->id}/todo_items.xml");
        $this->assertRequestContains("<todo-item><content>todo</content></todo-item>");
    }

    public function testArchiveProject()
    {
        $this->whenEntityIsUpdated();
        $this->api->archiveProject($this->project);
        $this->assertRequestContains('<project><status>archived</status></project>');
        $this->assertCalledUrl("/projects/{$this->project}.xml");
    }

    public function testCompleteTodoitem()
    {
        $this->whenEntityIsUpdated();
        $this->api->completeTodo($this->project, $this->id);
        $this->assertCalledUrl("/todo_items/{$this->id}/complete.xml");
    }

    public function testDeleteTodoitem()
    {
        $this->whenEntityIsDeleted();
        $this->api->deleteTodo($this->project, $this->id);
        $this->assertCalledUrl("/todo_items/{$this->id}.xml");
    }

    public function testDeleteTodolist()
    {
        $this->whenEntityIsDeleted();
        $this->api->deleteTodolist($this->project, $this->id);
        $this->assertCalledUrl("/todo_lists/{$this->id}.xml");
    }

    public function testGrantAccess()
    {
        $this->api->grantAccess($this->project, 'irrelevant argument');
        $this->assertApiWasNotCalled();
    }

    public function testRevokeAccess()
    {
        $this->api->revokeAccess($this->project, 'irrelevant argument');
        $this->assertApiWasNotCalled();
    }

    public function testBuildProjectUrl()
    {
        assertThat(
            $this->api->buildProjectUrl(
                (object) [
                    'bc__product_type' => $this->getApiType(),
                    'bc__account_href' => 'account url',
                ],
                $this->project
            ),
            is("account url/projects/{$this->project}")
        );
    }

    public function testThrowExceptionWhenIdOfCreatedEntityIsMissing()
    {
        $this->setExpectedException(BasecampMissingReturnValueException::class);
        $this->whenEntityIsCreated(
            function () {
                return $this->api->createProject($this->project);
            },
            ['headers' => 'response without location header']
        );
    }

    public function testThrowExceptionWhenEndpointReturnsInvalidContent()
    {
        $this->whenApiReturns('invalid');
        $this->setExpectedException(BasecampInvalidXmlException::class);
        $this->api->getProjects();
    }
}
