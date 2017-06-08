<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProjectToBasecamp
{
    private $basecampFactory;

    private $costlocker;
    /** @var \Costlocker\Integrations\Basecamp\Api\BasecampApi */
    private $basecamp;
    private $database;

    public function __construct(CostlockerClient $c, BasecampFactory $b, SyncDatabase $db)
    {
        $this->costlocker = $c;
        $this->basecampFactory = $b;
        $this->database = $db;
    }

    public function __invoke(SyncRequest $config)
    {
        $response = $this->costlocker->__invoke("/projects/{$config->costlockerProject}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];
        list($people, $activities) = $this->analyzeProjectItems($project['items']);

        $this->basecamp = $this->basecampFactory->__invoke($config->account);
        $bcProject = $this->upsertProject($project, $config);
        $bcProjectId = $bcProject['id'];
        if ($this->checkDeletedProject($bcProject)) {
            return [
                'basecamp' => 'project was deleted'
            ];
        }
        if ($config->areTodosEnabled) {
            $grantedPeople = $this->grantAccess($bcProjectId, $people);
            $bcProject['basecampPeople'] = $this->basecamp->getPeople($bcProjectId);
            $todolists = $this->createTodolists($bcProject, $activities);
            $delete = $this->deleteLegacyEntitiesInBasecamp($bcProject, $people, $todolists, $config);
        } else {
            $grantedPeople = [];
            $todolists = [];
            $delete = [];
        }

        $this->updateMapping($bcProject, $todolists, $delete, $config);

        return [
            'request' => get_object_vars($config),
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
                    'persons' => [],
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

    private function upsertProject(array $project, SyncRequest $config)
    {
        $existingProject = $this->database->findProject($project['id']);
        if ($existingProject) {
            return ['isCreated' => false, 'costlocker_id' => $project['id']] + $existingProject;
        }
        $name = "{$project['client']['name']} | {$project['name']}";
        return [
            'id' => $config->updatedBasecampProject ?: $this->basecamp->createProject($name, null, null),
            'costlocker_id' => $project['id'],
            'activities' => [],
            'isCreated' => true
        ];
    }

    private function checkDeletedProject(array $bcProject)
    {
        if ($bcProject['isCreated']) {
            return;
        }
        try {
            $this->basecamp->projectExists($bcProject['id']);
        } catch (Api\BasecampException $e) {
            $this->database->deleteProject($bcProject['costlocker_id'], $bcProject['id']);
            return true;
        }
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
        return [
            'id' => $this->basecamp->createTodo($bcProject['id'], $bcTodolist['id'], $task['name'], $assignee),
            'person_id' => $task['person_id'],
            'name' => $task['name'],
        ];
    }

    private function deleteLegacyEntitiesInBasecamp(array $bcProject, array $peopleFromCostlocker, array $currentActivities, SyncRequest $config)
    {
        $deleted = [
            'activities' => [],
            'tasks' => [],
            'persons' => [],
            'revoked' => [],
        ];

        if ($bcProject['isCreated'] || $config->isDeleteDisabled()) {
            return $deleted;
        }

        $bcTodolists = $this->basecamp->getTodolists($bcProject['id']);

        if ($config->isDeletingTodosEnabled) {
            foreach ($bcProject['activities'] as $activityId => $activity) {
                $bcTodolistId = $activity['id'];
                if (array_key_exists($bcTodolistId, $bcTodolists)) {
                    foreach (['tasks', 'persons'] as $type) {
                        foreach ($activity[$type] as $id => $mappedTodo) {
                            if (isset($currentActivities[$activityId][$type][$id])) {
                                continue;
                            }
                            if (array_key_exists($mappedTodo['id'], $bcTodolists[$bcTodolistId]->todoitems)) {
                                $this->basecamp->deleteTodo($bcProject['id'], $mappedTodo['id']);
                                unset($bcTodolists[$bcTodolistId]->todoitems[$mappedTodo['id']]);
                            }
                            $deleted[$type][$activityId][$id] = $id;
                        }
                    }
                    if (!isset($currentActivities[$activityId]) && !$bcTodolists[$bcTodolistId]->todoitems) {
                        $this->basecamp->deleteTodolist($bcProject['id'], $bcTodolistId);
                        $deleted['activities'][$activityId] = $activityId;
                    }
                } elseif (!isset($currentActivities[$activityId])) {
                    $deleted['activities'][$activityId] = $activityId;
                }
            }
        }

        if ($config->isRevokeAccessEnabled) {
            $assignedIds = [];
            foreach ($bcTodolists as $todolist) {
                foreach ($todolist->todoitems as $todoitem) {
                    $assignedIds[$todoitem->assignee_id] = $todoitem->assignee_id;
                }
            }

            foreach ($bcProject['basecampPeople'] as $email => $bcPerson) {
                if (!$bcPerson->admin &&
                    !array_key_exists($email, $peopleFromCostlocker) &&
                    !array_key_exists($bcPerson->id, $assignedIds)) {
                    $this->basecamp->revokeAccess($bcProject['id'], $bcPerson->id);
                    $deleted['revoked'][$email] = $email;
                }
            }
        }

        return $deleted;
    }

    private function updateMapping(array $bcProject, array $todolists, array $deleteSummary, SyncRequest $config)
    {
        if ($config->areTodosEnabled) {
            foreach ($todolists as $activityId => $activity) {
                if (!array_key_exists($activityId, $bcProject['activities'])) {
                    $bcProject['activities'][$activityId] = [];
                }
                $bcProject['activities'][$activityId] += [
                    'id' => $activity['id'],
                    'tasks' => [],
                    'persons' => [],
                ];
                foreach (['tasks', 'persons'] as $type) {
                    foreach ($activity[$type] as $taskId => $mappedTodo) {
                        $bcProject['activities'][$activityId][$type][$taskId] = $mappedTodo;
                    }
                }
            }

            foreach ($deleteSummary['activities'] as $activity) {
                unset($bcProject['activities'][$activity]);
            }
            foreach (['tasks', 'persons'] as $type) {
                foreach ($deleteSummary[$type] as $activityId => $tasks) {
                    foreach ($tasks as $taskId) {
                        unset($bcProject['activities'][$activityId][$type][$taskId]);
                    }
                }
            }
        }

        $this->database->upsertProject($bcProject['costlocker_id'], [
            'id' => $bcProject['id'],
            'account' => $this->basecampFactory->getAccount(),
            'activities' => $bcProject['activities'],
        ], $config->toSettings());
    }
}
