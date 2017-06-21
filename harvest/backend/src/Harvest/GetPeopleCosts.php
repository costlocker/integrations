<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetPeopleCosts
{
    private $project;
    private $analysis;

    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $this->project = $apiClient("/projects/{$r->query->get('peoplecosts')}")['project'];
        $this->analysis = $apiClient("/projects/{$r->query->get('peoplecosts')}/analysis?period=lifespan");

        $taskPersons = [];
        foreach ($this->analysis['tasks'] as $i => $task) {
            $allPeople = $apiClient(
                "/projects/{$r->query->get('peoplecosts')}/team_analysis?task_id={$task['task_id']}&period=lifespan"
            );
            $taskPersons[$task['task_id']] = $this->removePeopleWithNoTrackedTime($allPeople);
            if (!$taskPersons[$task['task_id']]) {
                unset($this->analysis['tasks'][$i]);
            }
        }

        $users = [];
        foreach ($apiClient('/people') as $person) {
            $users[$person['user']['id']] = [
                'email' => $person['user']['email'],
                'first_name' => $person['user']['first_name'],
                'last_name' => $person['user']['last_name'],
                'full_name' => "{$person['user']['first_name']} {$person['user']['last_name']}",
                'role' => $person['user']['is_admin'] ? 'ADMIN' : 'EMPLOYEE',
                'salary' => [
                    'payment' => 'hourly',
                    'hourly_rate' => $person['user']['cost_rate'] ?: 0,
                ],
            ];
        }

        return [
            'tasks' => array_map(
                function (array $task) use ($taskPersons, $users) {
                    return [
                        'id' => $task['task_id'],
                        'activity' => [
                            'name' => $task['name'],
                            'hourly_rate' => $this->calculateActivityRate($task, $taskPersons[$task['task_id']]),
                        ],
                        'hours' => [
                            'tracked' => $task['total_hours'],
                        ],
                        'people' => array_map(
                            function (array $person) use ($task, $users) {
                                return [
                                    'id' => $person['user_id'],
                                    'finance' => [
                                        'billed_rate' => $person['billed_rate'],
                                    ],
                                    'hours' => [
                                        'budget' => $person['total_hours'],
                                        'tracked' => $person['total_hours'],
                                    ],
                                    'person' => $users[$person['user_id']],
                                ];
                            },
                            $taskPersons[$task['task_id']]
                        ),
                        'finance' => [
                            'revenue' => $task['money_budget'] ?: 0,
                        ],
                    ];
                },
                $this->analysis['tasks']
            ),
            'people' => array_map(
                function (array $person) use ($users) {
                    return [
                        'id' => $person['user_id'],
                        'finance' => [
                            'billed_rate' => $person['billed_rate'],
                        ],
                        'hours' => [
                            'tracked' => $person['total_hours'],
                        ],
                        'person' => $users[$person['user_id']],
                    ];
                },
                $this->analysis['team_members']
            ),
        ];
    }

    private function removePeopleWithNoTrackedTime(array $persons)
    {
        return array_filter(
            $persons,
            function (array $person) {
                return $person['total_hours'];
            }
        );
    }

    private function calculateActivityRate(array $task, array $persons)
    {
        $personHours = array_sum(array_map(
            function (array $person) {
                return $person['total_hours'];
            },
            $persons
        ));
        return $task['money_budget'] / $personHours;
    }
}
