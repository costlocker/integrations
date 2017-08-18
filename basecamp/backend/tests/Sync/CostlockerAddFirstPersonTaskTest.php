<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\Event;

class CostlockerAddFirstPersonTaskTest extends GivenSynchronizer
{
    public function setUp()
    {
        parent::setUp();
        $this->request = [
            'account' => null,
            'costlockerProject' => 'irrelevant id',
            'areTodosEnabled' => true,
            'isDeletingTodosEnabled' => true,
            'isRevokeAccessEnabled' => true,
        ];
    }

    public function testFullProjectSynchronization()
    {
        $this->eventType = Event::MANUAL_SYNC;
        $basecampId = 'irrelevant project';
        $this->givenCostlockerProject('one-person.json');
        $this->whenProjectIsMapped(
            $basecampId,
            [
                1 => [
                    'id' => $basecampId,
                    'tasks' => [
                    ],
                    'persons' => [
                        1 => [
                            'id' => 'deleted todo',
                            'person_id' => 1,
                            'name' => 'Development',
                        ],
                    ],
                ],
            ]
        );
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists([$basecampId => ['deleted todo']]);
        $this->shouldCreateTodo()
            ->with($basecampId, $basecampId, 'Homepage', 1)
            ->andReturn($basecampId);
        $this->shouldCreateTodo()
            ->with($basecampId, $basecampId, 'Development', 2)
            ->andReturn($basecampId);
        $this->shouldDeleteTodos([['deleted todo']]);
        $this->synchronize();
        $this->assertMappingIs(
            [
                'id' => $basecampId,
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

    public function testWebhookSynchronization()
    {
        $this->eventType = Event::WEBHOOK_SYNC;
        $basecampId = 'irrelevant project';
        $this->givenCostlockerWebhook('update-person-and-tasks.json');
        $this->whenProjectIsMapped(
            $basecampId,
            [
                1 => [
                    'id' => $basecampId,
                    'tasks' => [
                    ],
                    'persons' => [
                        1 => [
                            'id' => 'deleted todo',
                            'person_id' => 1,
                            'name' => 'Development',
                        ],
                    ],
                ],
            ]
        );
        $this->shouldLoadBasecampPeople(
            [
                'John Doe (john@example.com)' => 'john@example.com',
            ],
            $basecampId
        );
        $this->givenBasecampTodolists([$basecampId => ['deleted todo']]);
        $this->shouldCreateTodo()
            ->with($basecampId, $basecampId, 'Homepage', 1)
            ->andReturn($basecampId);
        $this->shouldCreateTodo()
            ->with($basecampId, $basecampId, 'Contact', 1)
            ->andReturn($basecampId);
        $this->shouldDeleteTodos([['deleted todo']]);
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

    protected function givenCostlockerWebhook($file)
    {
        parent::givenWebhook($file, []);
    }
}
