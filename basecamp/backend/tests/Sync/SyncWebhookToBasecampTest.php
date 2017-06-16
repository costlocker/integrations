<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Entities\BasecampUser;
use Costlocker\Integrations\Entities\CostlockerUser;

class SyncWebhookToBasecampTest extends GivenCostlockerToBasecampSynchronizer
{
    protected $eventType = Event::WEBHOOK_SYNC;
    private $company;

    public function setUp()
    {
        parent::setUp();
        $this->company = new CostlockerCompany();
        $this->company->defaultBasecampUser = new BasecampUser();
        $this->company->defaultCostlockerUser = new CostlockerUser();
    }

    public function testIgnoreUnmappedProject()
    {
        $this->givenCostlockerWebhook('create-activity-and-persons.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(Event::RESULT_FAILURE);
    }

    public function testConvertNewActivityAndTaskToTodolistsAndTodos()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('create-activity-and-persons.json');
        $this->whenProjectIsMapped($basecampId);
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists([$basecampId => []]);
        $this->shouldCreateTodos(
            [
                'Development' => [
                    'Homepage' => 1,
                    'Development' => 2,
                ],
            ],
            $basecampId
        );
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
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
            ]
        );
    }

    public function testIgnoreProjectWhereTodosSyncIsDisabled()
    {
        $basecampId = 'irrelevant project';
        $this->request['areTodosEnabled'] = false;
        $this->givenCostlockerWebhook('create-activity-and-persons.json');
        $this->whenProjectIsMapped($basecampId, [], ['areTodosEnabled' => false]);
        $this->whenProjectExistsInBasecamp();
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertNoMappingInDatabase($basecampId);
    }

    public function testIgnoreUpdatedTaskOrActivity()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('update-person-and-tasks.json');
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
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists([$basecampId => []]);
        $this->shouldCreateTodo()
            ->with($basecampId, $basecampId, 'Contact', 1)
            ->andReturn($basecampId);
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
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
            ]
        );
    }

    public function testIgnoreChangeInTaskName()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('update-task-name.json');
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
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
            ]
             // no grantAccess, becasue grantAccess is executed only for item=person
        );
        $this->givenBasecampTodolists([$basecampId => []]);
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'activities' => $originalMapping,
            ]
        );
    }

    public function testDeleteTask()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('delete-task.json');
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
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists([$basecampId => [$basecampId]]);
        $this->shouldDeleteTodos([[$basecampId]]);
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
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
            ]
        );
    }

    public function testDeleteActivity()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('delete-activity.json');
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
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists(['deleted todolist' => ['first delete', 'second delete']]);
        $this->shouldDeleteTodos(['deleted todolist' => ['first delete', 'second delete']]);
        $this->synchronize();
        $this->assertNoMappingInDatabase($basecampId);
    }

    public function testIgnoreOtherEvents()
    {
        $this->givenCostlockerWebhook('unmapped-webhook.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(null);
    }

    public function testIgnoreWebhookThatIsMappedToNoCompany()
    {
        $this->company = null;
        $this->givenCostlockerWebhook('delete-activity.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(null);
    }

    public function testCreateProjectWhenCreatingAllowedInCompanySettings()
    {
        $this->company->settings['isCreatingBasecampProjectEnabled'] = true;
        $this->company->settings['areTodosEnabled'] = false;
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('create-project.json');
        $this->givenCostlockerProject('one-person.json');
        $this->shouldCreateProject()->once()->andReturn($basecampId);
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->shouldRegisterWebhooks();
        $this->synchronize(Event::RESULT_SUCCESS);
        $this->assertMappingIsNotEmpty();
    }

    private function shouldRegisterWebhooks()
    {
        $this->database->shouldRegisterWebhooks = true;
        $this->basecamp->shouldReceive('registerWebhook')->once();
        $this->costlocker->shouldReceive('__invoke')
            ->with('/webhooks', m::on(function ($data) {
                assertThat($data['events'], is(arrayWithSize(2)));
                return true;
            }))
            ->andReturn(new Response(200));
        $this->eventsLogger->shouldReceive('__invoke')->twice();
    }

    /** @dataProvider provideCompany */
    public function testDontCreateProjectWhenCompanyIs($loadCompany)
    {
        $this->company->settings['isCreatingBasecampProjectEnabled'] = true;
        $this->company = $loadCompany($this->company);
        $this->givenCostlockerWebhook('create-project.json');
        $this->shouldCreateProject()->never();
        $this->synchronize(null);
    }

    public function provideCompany()
    {
        return [
            'no company' => [
                function () {
                    return null;
                }
            ],
            'disabled creating project' => [
                function (CostlockerCompany $c) {
                    $c->settings['isCreatingBasecampProjectEnabled'] = false;
                    return $c;
                }
            ],
            'no costlocker account is selected' => [
                function (CostlockerCompany $c) {
                    $c->defaultCostlockerUser = null;
                    return $c;
                }
            ],
            'no basecamp account is selected' => [
                function (CostlockerCompany $c) {
                    $c->defaultBasecampUser = null;
                    return $c;
                }
            ],
            'disconnected basecamp account' => [
                function (CostlockerCompany $c) {
                    $c->defaultBasecampUser->deletedAt = new \DateTime();
                    return $c;
                }
            ],
        ];
    }

    protected function synchronize($expectedStatus = Event::RESULT_SUCCESS)
    {
        $this->companies->shouldReceive('findCompanyByWebhook')->andReturn($this->company);
        parent::synchronize($expectedStatus);
    }

    protected function givenCostlockerWebhook($file)
    {
        parent::givenWebhook($file, []);
    }
}
