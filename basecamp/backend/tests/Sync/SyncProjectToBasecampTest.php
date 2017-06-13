<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Basecamp\Api\BasecampAccessException;
use Costlocker\Integrations\Entities\Event;

class SyncProjectToBasecampTest extends GivenCostlockerToBasecampSynchronizer
{
    public function setUp()
    {
        parent::setUp();
        $this->request = [
            'account' => [], // should be int id, but it's asserted in assertEquals due to legacy
            'costlockerProject' => 'irrelevant id',
            'areTodosEnabled' => true,
        ];
    }

    protected function createSynchronizer(Synchronizer $s)
    {
        return new SyncProjectToBasecamp($s);
    }

    /** @dataProvider provideCreate */
    public function testCreateProject($updatedBasecampProject, $basecampId)
    {
        $this->request += [
            'mode' => 'add',
            'basecampProject' => $updatedBasecampProject,
        ];
        $this->givenCostlockerProject('one-person.json');
        $this->shouldCreateProject()
            ->times($updatedBasecampProject ? 0 : 1)
            ->with('ACME | Website', null, null)
            ->andReturn($basecampId);
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId,
            false
        );
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
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => 'Homepage',
                            ],
                        ],
                        'persons' => [
                            885 => [
                                'id' => $basecampId,
                                'person_id' => 885,
                                'name' => 'Development',
                            ],
                        ],
                    ]
                ],
            ]
        );
    }

    public function provideCreate()
    {
        return [
            'create new project' => [null, 'irrelevant new id'],
            'add to existing project' => ['irrelevant existing id', 'irrelevant existing id'],
        ];
    }

    public function testCreateProjectWithoutTodolists()
    {
        $basecampId = 'irrelevant project';
        $this->request['areTodosEnabled'] = false;
        $this->request['costlockerProject'] = ['first id'];
        $this->givenCostlockerProject('one-person.json');
        $this->shouldCreateProject()->once()->andReturn($basecampId);
        $this->shouldNotCreatePeopleOrTodosInBasecamp();
        $this->synchronize();
        $this->assertNoMappingInDatabase($basecampId);
    }

    public function testPartialUpdate()
    {
        $basecampId = 'existing id';
        $this->whenProjectIsMapped(
            $basecampId,
            [
                1 => [
                    'id' => $basecampId,
                    'tasks' => [
                        885 => [
                            'id' => $basecampId,
                            'person_id' => 1,
                            'name' => 'Homepage',
                        ],
                    ],
                    'persons' => [
                    ],
                ],
            ]
        );
        $this->givenCostlockerProject('one-person.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->shouldCreateTodo()->andReturn('new id');
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => 'Homepage',
                            ],
                        ],
                        'persons' => [
                            885 => [
                                'id' => 'new id',
                                'person_id' => 885,
                                'name' => 'Development',
                            ],
                        ],
                    ]
                ],
            ]
        );
    }

    public function testPartialDelete()
    {
        $basecampId = 'irrelevant project';
        $this->whenProjectIsMapped(
            $basecampId,
            [
                1 => [
                    'id' => $basecampId,
                    'tasks' => [
                        885 => [
                            'id' => $basecampId,
                            'person_id' => 1,
                            'name' => '',
                        ],
                        900 => [
                            'id' => 'deleted todo',
                            'person_id' => 1,
                            'name' => '',
                        ],
                        901 => [
                            'id' => 'unknown todo',
                            'person_id' => 1,
                            'name' => '',
                        ],
                    ],
                    'persons' => [
                        885 => [
                            'id' => $basecampId,
                            'person_id' => 1,
                            'name' => '',
                        ],
                    ],
                ],
            ]
        );
        $this->request += [
            'isDeletingTodosEnabled' => true,
            'isRevokeAccessEnabled' => true,
        ];
        $this->givenCostlockerProject('one-person.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists([$basecampId => ['deleted todo']]);
        $this->shouldDeleteTodos([['deleted todo']]);
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'account' => [],
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [
                            885 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => '',
                            ],
                        ],
                        'persons' => [
                            885 => [
                                'id' => $basecampId,
                                'person_id' => 1,
                                'name' => '',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    public function testFullDelete()
    {
        $basecampId = 'irrelevant project';
        $this->whenProjectIsMapped(
            $basecampId,
            [
                1 => [
                    'id' => 'non-empty todolist',
                    'tasks' => [
                        885 => [
                            'id' => 'existing todo',
                            'person_id' => 1,
                            'name' => '',
                        ],
                    ],
                    'persons' => [
                        885 => [
                            'id' => 'unknown basecamp id',
                            'person_id' => 885,
                            'name' => '',
                        ],
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
            ]
        );
        $this->request += [
            'isDeletingTodosEnabled' => true,
            'isRevokeAccessEnabled' => true,
        ];
        $this->givenCostlockerProject('empty-project.json');
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ]
        );
        $this->givenBasecampTodolists([
            'non-empty todolist' => [
                'existing todo',
                'todo manually created in BC (non-empty todolist is not deleted)' => 2,
            ],
            'empty todolist' => [],
        ]);
        $this->shouldDeleteTodos(['empty todolist' => ['existing todo']]);
        $this->shouldRevokeAccessToOnePerson();
        $this->synchronize();
        // non-empty todolist is not deleted in BC, but it is removed from mapping
        $this->assertNoMappingInDatabase($basecampId);
    }

    public function testProjectDeletedInBasecampIsNotDeletedInDatabase()
    {
        $this->whenProjectIsMapped('id of deleted project in basecamp');
        $this->givenCostlockerProject('empty-project.json');
        $this->whenProjectExistsInBasecamp()->andThrow(BasecampAccessException::class);
        $this->synchronize(Event::RESULT_FAILURE);
        $this->assertMappingIsNotEmpty();
    }
}
