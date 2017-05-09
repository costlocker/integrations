<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetPeopleCosts
{
    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $rawProject = $apiClient("/projects/{$r->query->get('peoplecosts')}/analysis?period=lifespan");

        $taskPersons = [];
        foreach ($rawProject['tasks'] as $task) {
            $taskPersons[$task['task_id']] = $apiClient("/projects/{$r->query->get('peoplecosts')}/team_analysis?task_id={$task['task_id']}&period=lifespan");
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
                    'hourly_rate' => $person['user']['cost_rate'],
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
                            'hourly_rate' => $task['billed_rate'],
                        ],
                        'hours' => [
                            'tracked' => $task['total_hours'],
                        ],
                        'people' => array_map(
                            function (array $person) use ($users) {
                                return [
                                    'id' => $person['user_id'],
                                    'finance' => [
                                        'billed_rate' => $person['billed_rate'],
                                    ],
                                    'hours' => [
                                        'budget' => $person['projected_hours'] ?? $person['total_hours'],
                                        'tracked' => $person['total_hours'],
                                    ],
                                    'person' => $users[$person['user_id']],
                                ];
                            },
                            $taskPersons[$task['task_id']]
                        ),
                    ];
                },
                $rawProject['tasks']
            ),
            'people' => array_map(
                function (array $person) use ($users) {
                    return [
                        'id' => $person['user_id'],
                        'finance' => [
                            'billed_rate' => $person['billed_rate'],
                        ],
                        'hours' => [
                            'budget' => $person['projected_hours'] ?? $person['total_hours'],
                            'tracked' => $person['total_hours'],
                        ],
                        'person' => $users[$person['user_id']],
                    ];
                },
                $rawProject['team_members']
            ),
        ];
    }
}
