<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProject
{
    private $basecampFactory;

    private $costlocker;
    /** @var \Costlocker\Integrations\Basecamp\Api\BasecampApi */
    private $basecamp;
    private $database;

    public function __construct(CostlockerClient $c, BasecampFactory $b, array $db = [])
    {
        $this->costlocker = $c;
        $this->basecampFactory = $b;
        $this->database = $db;
    }

    public function __invoke(array $config)
    {
        $response = $this->costlocker->__invoke("/projects/{$config['costlockerProject']}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];
        list($people, $activities) = $this->analyzeProjectItems($project['items']);

        $this->basecamp = $this->basecampFactory->__invoke($config['account']);
        $bcProject = $this->upsertProject($project);
        $bcProjectId = $bcProject['id'];
        $grantedPeople = $this->grantAccess($bcProjectId, $people);
        $bcProject['basecampPeople'] = $this->basecamp->getPeople($bcProjectId);
        $todolists = $this->createTodolists($bcProject, $activities);

        if ($bcProject['isUpdated'] && $config['isDeleteEnabled']) {
            $delete = $this->deleteLegacyEntitiesInBasecamp($bcProject);
        } else {
            $delete = [];
        }

        return [
            'costlocker' => $project,
            'basecamp' => [
                'id' => $bcProjectId,
                'people' => $grantedPeople,
                'activities' => $todolists,
                'delete' => $delete,
            ],
        ];
    }

    private function analyzeProjectItems(array $projectItems)
    {
        $persons = [];
        $personsMap = [];
        $activities = [];
        foreach ($projectItems as $item) {
            if ($item['item']['type'] == 'activity') {
                $activities[$item['item']['activity_id']] = [
                    'id' => $item['item']['activity_id'],
                    'name' => $item['activity']['name'],
                    'tasks' => [],
                ];
            }
            if ($item['item']['type'] == 'person') {
                $person = $item['person'];
                $persons[$person['email']] = "{$person['first_name']} {$person['last_name']}";
                $personsMap[$item['item']['person_id']] = $person['email'];
            }
            if (isset($item['hours']['is_aggregation']) && !$item['hours']['is_aggregation']) {
                $personId = $item['item']['person_id'];
                if ($item['item']['type'] == 'person') {
                    $activities[$item['item']['activity_id']]['persons'][$personId] = [
                        'task_id' => null,
                        'person_id' => $personId,
                        'name' => $activities[$item['item']['activity_id']]['name'],
                        'email' => $item['person']['email'],
                    ];
                } else {
                    $taskId = $item['item']['task_id'];
                    $activities[$item['item']['activity_id']]['tasks'][$taskId] = [
                        'task_id' => $taskId,
                        'person_id' => $personId,
                        'name' => $item['task']['name'],
                        'email' => $personsMap[$item['item']['person_id']],
                    ];
                }
            }
        }
        return [$persons, array_reverse($activities, true)];
    }

    private function upsertProject(array $project)
    {
        if (array_key_exists($project['id'], $this->database)) {
            return ['isUpdated' => true] + $this->database[$project['id']];
        }
        $name = "{$project['client']['name']} | {$project['name']}";
        return [
            'id' => $this->basecamp->createProject($name, null, null),
            'activities' => [],
            'isUpdated' => false
        ];
    }

    private function grantAccess($bcProjectId, array $peopleFromCostlocker)
    {
        $peopleEmails = array();
        foreach ($peopleFromCostlocker as $email => $fullname) {
            $peopleEmails["{$fullname} ({$email})"] = $email;
        }
        if ($peopleEmails) {
            $this->basecamp->grantAccess($bcProjectId, $peopleEmails);
        }
        return $peopleEmails;
    }

    private function createTodolists(array $bcProject, array $activities)
    {
        $mapping = [];
        foreach ($activities as $activityId => $activity) {
            $bcTodolist = $this->upsertTodolist($bcProject, $activity);
            $todos = [
                'tasks' => [],
                'persons' => [],
            ];
            foreach ($activity['tasks'] as $id => $task) {
                $todos['tasks'][$id] = $this->upsertTodo($bcProject, $bcTodolist, $task);
            }
            foreach ($activity['persons'] as $id => $personWithoutTasks) {
                $todos['persons'][$id] = $this->upsertTodo($bcProject, $bcTodolist, $personWithoutTasks);
            }
            $mapping[$activityId] = ['id' => $bcTodolist['id']] + $todos;
        }
        return $mapping;
    }

    private function upsertTodolist(array $bcProject, array $activity)
    {
        if (array_key_exists($activity['id'], $bcProject['activities'])) {
            return $bcProject['activities'][$activity['id']];
        }
        return [
            'id' => $this->basecamp->createTodolist($bcProject['id'], $activity['name']),
            'tasks' => [],
            'persons' => [],
        ];
    }

    private function upsertTodo(array $bcProject, array $bcTodolist, array $task)
    {
        if (array_key_exists($task['task_id'], $bcTodolist['tasks'])) {
            return $bcTodolist['tasks'][$task['task_id']];
        }
        if (!$task['task_id'] && array_key_exists($task['person_id'], $bcTodolist['persons'])) {
            return $bcTodolist['persons'][$task['person_id']];
        }
        $assignee = $bcProject['basecampPeople'][$task['email']]->id;
        return $this->basecamp->createTodo($bcProject['id'], $bcTodolist['id'], $task['name'], $assignee);
    }

    private function deleteLegacyEntitiesInBasecamp(array $bcProject)
    {
        $summary = [
            'todolists' => [],
            'tasks' => [],
        ];
        $bcTodolists = $this->basecamp->getTodolists($bcProject['id']);
        foreach ($bcProject['activities'] as $activity) {
            $bcTodolistId = $activity['id'];
            if (array_key_exists($bcTodolistId, $bcTodolists)) {
                foreach (array_merge($activity['tasks'], $activity['persons']) as $bcTodoId) {
                    if (array_key_exists($bcTodoId, $bcTodolists[$bcTodolistId]->todoitems)) {
                        $this->basecamp->deleteTodo($bcProject, $bcTodolistId, $bcTodoId);
                        $summary['tasks'][] = $bcTodoId;
                        unset($bcTodolists[$bcTodolistId]->todoitems[$bcTodoId]);
                    }
                }
                if (!$bcTodolists[$bcTodolistId]->todoitems) {
                    $this->basecamp->deleteTodolist($bcProject['id'], $bcTodolistId);
                    $summary['todolists'][] = $activity;
                }
            }
        }
        return $summary;
    }
}
