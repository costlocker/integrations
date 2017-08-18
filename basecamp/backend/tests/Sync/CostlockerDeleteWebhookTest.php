<?php

namespace Costlocker\Integrations\Sync;

class CostlockerDeleteWebhookTest extends GivenSynchronizer
{
    public function testDeleteActivity()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhookSync('delete-activity.json');
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
                    // second person was already deleted when last task was deleted in BC
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
        $this->givenBasecampTodolists(['deleted todolist' => ['first delete']]);
        $this->shouldDeleteTodos(['deleted todolist' => ['first delete']]);
        $this->synchronize();
        $this->assertNoMappingInDatabase($basecampId);
    }

    public function testDeletePerson()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhookSync('delete-person.json');
        $this->whenProjectIsMapped($basecampId, [
            1 => [
                'id' => $basecampId,
                'tasks' => [
                    971 => [
                        'id' => 'first delete',
                        'person_id' => 6,
                        'name' => 'Homepage',
                    ],
                ],
                'persons' => [
                    1 => [
                        'id' => 'second delete',
                        'person_id' => 1,
                        'name' => 'Development',
                    ],
                    // second person was already deleted when last task was deleted in BC
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
        $this->givenBasecampTodolists([$basecampId => ['first delete', 'second delete']]);
        $this->shouldDeleteTodos([['first delete', 'second delete']]);
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'tasks' => [],
                        'persons' => [],
                    ],
                ],
            ]
        );
    }

    public function testDeleteTask()
    {
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhookSync('delete-task.json');
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
            ]
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
}
