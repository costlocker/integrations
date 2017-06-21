<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetPeopleCosts
{
    private $project;
    private $analysis;
    private $activeTasks;

    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $this->project = $apiClient("/projects/{$r->query->get('peoplecosts')}")['project'];
        $this->analysis = $apiClient("/projects/{$r->query->get('peoplecosts')}/analysis?period=lifespan");
        $this->activeTasks = [
            'total' => 0,
            'people' => [],
            'tasks' => [],
        ];

        $taskPersons = [];
        foreach ($this->analysis['tasks'] as $i => $task) {
            $allPeople = $apiClient(
                "/projects/{$r->query->get('peoplecosts')}/team_analysis?task_id={$task['task_id']}&period=lifespan"
            );
            $taskPersons[$task['task_id']] = $this->removePeopleWithNoTrackedTime($allPeople);
            $this->activeTasks['total'] += count($taskPersons[$task['task_id']]);
            $this->activeTasks['tasks'][$task['task_id']] = count($taskPersons[$task['task_id']]);
            foreach ($taskPersons[$task['task_id']] as $person) {
                if (!isset($this->activeTasks['people'][$person['user_id']])) {
                    $this->activeTasks['people'][$person['user_id']] = 0;
                }
                $this->activeTasks['people'][$person['user_id']]++;
            }
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

        $fixedBudget = $this->isFixedBudget($this->project) && $this->analysis['tasks']
            ? ($r->query->get('fixedBudget', 0) / count($this->analysis['tasks']))
            : 0;

        return [
            'tasks' => array_map(
                function (array $task) use ($taskPersons, $users, $fixedBudget) {
                    $budget = $fixedBudget ? $fixedBudget : ($task['money_budget'] ?? 0);
                    return [
                        'id' => $task['task_id'],
                        'activity' => [
                            'name' => $task['name'],
                            'hourly_rate' => $this->calculateActivityRate(
                                $budget,
                                $task,
                                $taskPersons[$task['task_id']]
                            ),
                        ],
                        'hours' => [
                            'tracked' => $task['total_hours'],
                        ],
                        'people' => array_map(
                            function (array $person) use ($users, $task) {
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
                        'finance' => [
                            'revenue' => $budget,
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

    private function isFixedBudget($project)
    {
        return 
            $project['billable'] &&
            $project['bill_by'] == 'none' &&
            $project['budget_by'] == 'none';
    }

    private function calculateActivityRate($budget, array $task, array $persons)
    {
        if (!$this->project['billable']) {
            return 0;
        }
        $personHours = array_sum(array_map(
            function (array $person) {
                return $person['total_hours'];
            },
            $persons
        ));
        return $budget / $personHours;
    }

    private function calculatePersonEstimate(array $person, array $task)
    {
        if (!$this->project['billable']) {
            switch ($this->project['budget_by']) {
                case 'project':
                    $itemsCount = count($this->analysis['tasks']) * $this->activeTasks['total'];
                    return $this->project['budget'] / $itemsCount;
                case 'person':
                    $itemsCount = $this->activeTasks['people'][$person['user_id']];
                    return $person['budget'] / $itemsCount;
                case 'task':
                    $itemsCount = $this->activeTasks['tasks'][$task['task_id']];
                    return $task['budget'] / $itemsCount;
            }
        }
        return $person['total_hours'];
    }
}
