<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Entities\CostlockerCompany;

class CostlockerWebhookToBasecampTest extends GivenSynchronizer
{
    public function testIgnoreUnmappedProject()
    {
        $this->givenCostlockerWebhookSync('create-activity-and-persons.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(Event::RESULT_FAILURE);
    }

    public function testConvertNewActivityAndTaskToTodolistsAndTodos()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhookSync('create-activity-and-persons.json');
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
        $this->givenCostlockerWebhookSync('create-activity-and-persons.json');
        $this->whenProjectIsMapped($basecampId, [], ['areTodosEnabled' => false]);
        $this->whenProjectExistsInBasecamp();
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(Event::RESULT_NOCHANGE);
        $this->assertNoMappingInDatabase($basecampId);
    }

    public function testIgnoreUpdatedTaskOrActivity()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhookSync('update-person-and-tasks.json');
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
        $this->givenCostlockerWebhookSync('update-task-name.json');
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

    public function testIgnoreOtherEvents()
    {
        $this->givenCostlockerWebhookSync('unmapped-webhook.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize(null);
    }

    public function testIgnoreWebhookThatIsMappedToNoCompany()
    {
        $this->database->company = null;
        $this->givenCostlockerWebhookSync('delete-activity.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->shouldLogInvalidWebhook(Event::INVALID_COSTLOCKER_WEBHOOK_UNKNOWN_COMPANY);
        $this->synchronize(null);
    }

    public function testCreateProjectWhenCreatingAllowedInCompanySettings()
    {
        $this->database->company->settings['isCreatingBasecampProjectEnabled'] = true;
        $this->database->company->settings['areTodosEnabled'] = false;
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhookSync('create-project.json');
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
        $this->database->company->settings['isCreatingBasecampProjectEnabled'] = true;
        $this->database->company = $loadCompany($this->database->company);
        $this->givenCostlockerWebhookSync('create-project.json');
        $this->shouldCreateProject()->never();
        if (!$this->database->company) {
            $this->shouldLogInvalidWebhook(Event::INVALID_COSTLOCKER_WEBHOOK_UNKNOWN_COMPANY);
        }
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

    public function testIgnoreWehbhookWithInvalidSignature()
    {
        $this->hasWebhookValidSignature = false;
        $this->givenCostlockerWebhookSync('update-person-and-tasks.json');
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->shouldLogInvalidWebhook(Event::INVALID_COSTLOCKER_WEBHOOK_SIGNATURE);
        $this->synchronize(null);
    }

    private function shouldLogInvalidWebhook($expectedEvent)
    {
        $this->eventsLogger->shouldReceive('__invoke')->once()->with($expectedEvent, m::any());
    }
}
