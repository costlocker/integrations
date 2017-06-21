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
                                        'budget' => $this->calculatePersonEstimate($person, $task),
                                        'tracked' => $person['total_hours'],
                                    ],
                                    'person' => $users[$person['user_id']],
                                ];
                            },
                            $taskPersons[$task['task_id']]
                        ),
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
        switch ($this->project['bill_by']) {
            case 'People':
                $personRates = array_map(
                    function (array $person) {
                        return $person['billed_rate'];
                    },
                    $persons
                );
                return array_sum($personRates) / count($personRates);
            case 'Project':
            case 'Tasks':
                return $task['billed_rate'];
        }
        return 0;
    }

    private function calculatePersonEstimate(array $person, array $task)
    {
        switch ($this->project['budget_by']) {
            case 'project':
                $itemsCount = count($this->analysis['tasks']) * count($this->analysis['team_members']);
                return $this->project['budget'] / $itemsCount;
            case 'person':
                return $person['budget'] / count($this->analysis['tasks']);
            case 'task':
                return $task['budget'] / count($this->analysis['team_members']);
            case 'task_fees':
                if (!$task['billed_rate']) {
                    return 0;
                }
                $hoursBudget = $task['money_budget'] / $task['billed_rate'];
                return $hoursBudget / count($this->analysis['team_members']);
        }
        return 0;
    }
}
