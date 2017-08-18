<?php

namespace Costlocker\Integrations\Sync;

class CostlockerAddFirstPersonTaskTest extends GivenSynchronizer
{
    private $basecampId = 'irrelevant id';

    public function setUp()
    {
        parent::setUp();
        $this->request = [
            'costlockerProject' => 'irrelevant id',
        ];
    }

    public function testFullProjectSynchronization()
    {
        $this->givenCostlockerProjectSync('one-person.json');
        $this->shouldDeletePersonalTodo(
            [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ],
            [
                'Homepage' => 1,
                'Development' => 2,
            ]
        );
    }

    public function testWebhookSynchronization()
    {
        $this->givenCostlockerWebhookSync('update-person-and-tasks.json');
        $this->shouldDeletePersonalTodo(
            [
                'John Doe (john@example.com)' => 'john@example.com',
            ],
            [
                'Homepage' => 1,
                'Contact' => 1,
            ]
        );
    }

    private function shouldDeletePersonalTodo(array $basecampPeople, array $createdTodos)
    {
        $this->whenProjectIsMapped(
            $this->basecampId,
            [
                1 => [
                    'id' => $this->basecampId,
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
        $this->shouldLoadBasecampPeople($basecampPeople, $this->basecampId);
        $this->givenBasecampTodolists([$this->basecampId => ['deleted todo']]);
        foreach ($createdTodos as $todo => $assignee) {
            $this->shouldCreateTodo()
                ->with($this->basecampId, $this->basecampId, $todo, $assignee)
                ->andReturn($this->basecampId);
        }
        $this->shouldDeleteTodos([['deleted todo']]);
        $this->synchronize();
        $this->assertMappingIs(function (array $mapping) {
            assertThat($mapping[1]['persons'], not(hasKey(1)));
        });
    }
}
