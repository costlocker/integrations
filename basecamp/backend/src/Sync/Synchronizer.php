<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Basecamp\Api\BasecampException;
use Costlocker\Integrations\Auth\GetUser;

class Synchronizer
{
    private $costlocker;
    private $basecampFactory;
    private $getUser;

    /** @var \Costlocker\Integrations\Basecamp\Api\BasecampApi */
    private $basecamp;
    private $database;

    public function __construct(CostlockerClient $c, GetUser $u, BasecampFactory $b, SyncDatabase $db)
    {
        $this->costlocker = $c;
        $this->getUser = $u;
        $this->basecampFactory = $b;
        $this->database = $db;
    }

    public function findProject($costlockerId)
    {
        return $this->database->findProject($costlockerId);
    }

    public function __invoke(SyncProjectRequest $r, SyncRequest $config)
    {
        if ($r->isCompleteProjectSynchronized) {
            $this->loadProjectFromCostlocker($r, $config);
        }

        $result = new SyncResult($r, $config);
        $this->basecamp = $this->basecampFactory->__invoke($config->account);
        $bcProject = $this->upsertProject($r);

        if (!$bcProject) {
            $result->basecampChangelog->error = "{$r->costlockerId} is not mapped";
            return $result;
        }

        list($people, $activities) = $this->analyzeProjectItems($bcProject, $r);

        $result->basecampProjectId = $bcProject['id'];
        $result->basecampChangelog->isCreated = $bcProject['isCreated'];

        if ($this->checkDeletedProject($bcProject)) {
            $result->basecampChangelog->error = "Project {$result->basecampProjectId} is not available in Basecamp";
            $result->mappedProject = $this->database->findBasecampProject($r->costlockerId);
            return $result;
        }

        if ($config->areTodosEnabled) {
            $this->grantAccess($result->basecampProjectId, $people, $result->basecampChangelog);
            $bcProject['basecampPeople'] = $this->basecamp->getPeople($result->basecampProjectId);
            $this->createTodolists($bcProject, $activities, $result->basecampChangelog);
            $this->deleteLegacyEntitiesInBasecamp($bcProject, $people, $activities, $config, $result->basecampChangelog);
            $this->updateMapping($bcProject, $result->basecampChangelog);
        }

        if ($config->areTasksEnabled) {
            if ($this->basecamp->canBeSynchronizedFromBasecamp()) {
                $this->synchronizePeopleCosts($bcProject, $config, $r, $result->costlockerChangelog);
                $this->updateMapping($bcProject, $result->costlockerChangelog);
            } else {
                $config->areTasksEnabled = false;
                $config->isDeletingTasksEnabled = false;
                $config->isCreatingActivitiesEnabled = false;
                $config->isDeletingActivitiesEnabled = false;
                $config->isBasecampWebhookEnabled = false;
            }
        }

        $this->saveProject($bcProject, $result);

        return $result;
    }

    private function loadProjectFromCostlocker(SyncProjectRequest $r, SyncRequest $config)
    {
        $this->getUser->overrideCostlockerUser($r->costlockerUser);
        $project = $this->findProjectInCostlocker($config->costlockerProject);
        $r->costlockerId = $project['id'];
        $r->projectItems = $project['items'];
        $r->createProject = function ($createBasecampProject) use ($project, $config) {
            $projectId = $project['project_id']['id'] ?? null;
            $name =
                "{$project['client']['name']} | {$project['name']}" .
                ($projectId ? " [{$projectId}]" : '');
            return $config->updatedBasecampProject ?: $createBasecampProject($name, $config->basecampClassicCompanyId);
        };
    }

    private function findProjectInCostlocker($costlockerId)
    {
        $response = $this->costlocker->__invoke("/projects/{$costlockerId}?types=peoplecosts");
        return json_decode($response->getBody(), true)['data'];
    }

    private function upsertProject(SyncProjectRequest $r)
    {
        $existingProject = $this->database->findProject($r->costlockerId);
        if ($existingProject) {
            return ['isCreated' => false, 'costlocker_id' => $r->costlockerId] + $existingProject;
        }
        $projectId = $r->createProject->__invoke(
            function ($name, $basecampClassicCompanyId = null) {
                return $this->basecamp->createProject($name, $basecampClassicCompanyId, null);
            }
        );
        if ($projectId) {
            return [
                'id' => $projectId,
                'costlocker_id' => $r->costlockerId,
                'activities' => [],
                'isCreated' => true
            ];
        }
        return null;
    }

    private function analyzeProjectItems(array $bcProject, SyncProjectRequest $request)
    {
        $persons = [];
        $personsMap = [];
        $activities = [];
        foreach ($request->projectItems as $item) {
            $action = $item['action'] ?? 'upsert';
            if (
                ($item['item']['type'] == 'activity' || isset($item['activity']['name'])) &&
                !array_key_exists($item['item']['activity_id'], $activities)
            ) {
                $activities[$item['item']['activity_id']] = [
                    'id' => $item['item']['activity_id'],
                    'name' => $item['activity']['name'],
                    'isDeleted' => $action == 'delete',
                    'upsert' => [
                        'tasks' => [],
                        'persons' => [],
                    ],
                    'delete' => [
                        'tasks' => [],
                        'persons' => [],
                    ],
                ];
            }
            if ($item['item']['type'] == 'person') {
                $person = $item['person'];
                $persons[$person['email']] = "{$person['first_name']} {$person['last_name']}";
                $personsMap[$item['item']['person_id']] = $person['email'];
            }
            if (in_array($item['item']['type'], ['person', 'task'])) {
                $personId = $item['item']['person_id'];
                if ($item['item']['type'] == 'person') {
                    $activities[$item['item']['activity_id']][$action]['persons'][$personId] = [
                        'task_id' => null,
                        'person_id' => $personId,
                        'name' => $activities[$item['item']['activity_id']]['name'],
                        'email' => $item['person']['email'],
                    ];
                } else {
                    $taskId = $item['item']['task_id'];
                    $activities[$item['item']['activity_id']][$action]['tasks'][$taskId] = [
                        'task_id' => $taskId,
                        'person_id' => $personId,
                        'name' => $item['task']['name'],
                        'email' => isset($item['person']['email'])
                            ? $item['person']['email'] : $personsMap[$item['item']['person_id']],
                    ];
                    unset($activities[$item['item']['activity_id']]['upsert']['persons'][$personId]);
                }
            }
        }

        if ($request->isCompleteProjectSynchronized) {
            foreach ($bcProject['activities'] as $activityId => $activity) {
                if (!isset($activities[$activityId])) {
                    $activities[$activityId] = [
                        'id' => $activity['id'],
                        'isDeleted' => true,
                        'upsert' => [
                            'tasks' => [],
                            'persons' => [],
                        ],
                        'delete' => [
                            'tasks' => [],
                            'persons' => [],
                        ],
                    ];
                }

                foreach (['tasks', 'persons'] as $type) {
                    foreach ($activity[$type] as $id => $mappedTodo) {
                        if (isset($activities[$activityId]['upsert'][$type][$id])) {
                            continue;
                        }
                        $activities[$activityId]['delete'][$type][$id] = $mappedTodo;
                    }
                }
            }
        }

        return [$persons, array_reverse($activities, true)];
    }

    private function checkDeletedProject(array $bcProject)
    {
        if ($bcProject['isCreated']) {
            return;
        }
        try {
            $this->basecamp->projectExists($bcProject['id']);
        } catch (BasecampException $e) {
            return true;
        }
    }

    private function grantAccess($bcProjectId, array $peopleFromCostlocker, SyncChangelog $changelog)
    {
        foreach ($peopleFromCostlocker as $email => $fullname) {
            $changelog->grantedPeople["{$fullname} ({$email})"] = $email;
        }
        if ($changelog->grantedPeople) {
            $this->basecamp->grantAccess($bcProjectId, $changelog->grantedPeople);
        }
    }

    private function createTodolists(array $bcProject, array $activities, SyncChangelog $changelog)
    {
        foreach ($activities as $activityId => $activity) {
            if ($activity['isDeleted']) {
                continue;
            }
            $bcTodolist = $this->upsertTodolist($bcProject, $activity);
            $changelog->initActivity($activityId, $bcTodolist['id'], $bcTodolist['isCreated']);
            foreach (['tasks', 'persons'] as $type) {
                foreach ($activity['upsert'][$type] as $id => $task) {
                    $todo = $this->upsertTodo($bcProject, $bcTodolist, $task);
                    if (!$todo['isCreated']) {
                        continue;
                    }
                    $changelog->addTask($activityId, $type, $id, $todo);
                }
            }
        }
    }

    private function upsertTodolist(array $bcProject, array $activity)
    {
        if (array_key_exists($activity['id'], $bcProject['activities'])) {
            return $bcProject['activities'][$activity['id']] + ['isCreated' => false];
        }
        return [
            'id' => $this->basecamp->createTodolist($bcProject['id'], $activity['name']),
            'tasks' => [],
            'persons' => [],
            'isCreated' => true,
        ];
    }

    private function upsertTodo(array $bcProject, array $bcTodolist, array $task)
    {
        if (array_key_exists($task['task_id'], $bcTodolist['tasks'])) {
            return $bcTodolist['tasks'][$task['task_id']] + ['isCreated' => false];
        }
        if (!$task['task_id'] && array_key_exists($task['person_id'], $bcTodolist['persons'])) {
            return $bcTodolist['persons'][$task['person_id']] + ['isCreated' => false];
        }
        $assignee = $bcProject['basecampPeople'][$task['email']]->id;
        return [
            'id' => $this->basecamp->createTodo($bcProject['id'], $bcTodolist['id'], $task['name'], $assignee),
            'person_id' => $task['person_id'],
            'name' => $task['name'],
            'isCreated' => true,
        ];
    }

    private function deleteLegacyEntitiesInBasecamp(
        array $bcProject, array $peopleFromCostlocker, array $activities, SyncRequest $config, SyncChangelog $changelog
    ) {
        if ($bcProject['isCreated'] || $config->isDeleteDisabled()) {
            return;
        }

        $bcTodolists = $this->basecamp->getTodolists($bcProject['id']);

        if ($config->isDeletingTodosEnabled) {
            foreach ($activities as $activityId => $activity) {
                $bcTodolistId = $bcProject['activities'][$activityId]['id'] ?? null;
                foreach (['tasks', 'persons'] as $type) {
                    foreach ($activity['delete'][$type] as $id => $task) {
                        $bcTodoId = $bcProject['activities'][$activityId][$type][$id]['id'];
                        if (isset($bcTodolists[$bcTodolistId]) && array_key_exists($bcTodoId, $bcTodolists[$bcTodolistId]->todoitems)) {
                            $this->basecamp->deleteTodo($bcProject['id'], $bcTodoId);
                            unset($bcTodolists[$bcTodolistId]->todoitems[$bcTodoId]);
                        }
                        $changelog->deleteTask($activityId, $type, $id);
                    }
                }
                if ($activity['isDeleted']) {
                    if (isset($bcTodolists[$bcTodolistId]) && !$bcTodolists[$bcTodolistId]->todoitems) {
                        $this->basecamp->deleteTodolist($bcProject['id'], $bcTodolistId);
                    }
                    $changelog->deleteActivity($activityId);
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
                    $changelog->revokeAccess($email);
                }
            }
        }
    }

    private function synchronizePeopleCosts(
        array $bcProject, SyncRequest $config, SyncProjectRequest $projectRequest, SyncChangelog $changelog
    ) {
        if ($bcProject['isCreated']) {
            return;
        }

        $bcTodolists = $this->basecamp->getTodolists($bcProject['id']);
        $tasksUpdate = [];
        foreach ($bcTodolists as $todolistId => $bcTodolist) {
            $activityId = $this->findByBasecampId($bcProject['activities'], $todolistId);
            if (!$activityId) {
                continue;
            }
            foreach ($bcTodolist->todoitems as $todoId => $todo) {
                if (
                    $this->findByBasecampId($bcProject['activities'][$activityId]['tasks'], $todoId) ||
                    $this->findByBasecampId($bcProject['activities'][$activityId]['persons'], $todoId) ||
                    !$todo->assignee
                ) {
                    continue;
                }
                $tasksUpdate[] = [
                    'item' => [
                        'type' => 'task',
                        'activity_id' => $activityId,
                    ],
                    'person' => $todo->assignee + [
                        'role' => 'EMPLOYEE',
                        'salary' => [
                            'payment' => 'hourly',
                            'hourly_rate' => 0,
                        ],
                    ],
                    'task' => $todo->content,
                    'hours' => 0,
                    'basecamp' => [
                        'todo_id' => $todoId,
                        'todolist_id' => $todolistId,
                    ],
                ];
            }

            if (!$config->isDeletingTasksEnabled) {
                continue;
            }
            foreach (['tasks', 'persons'] as $type) {
                foreach ($bcProject['activities'][$activityId][$type] as $id => $mappedTodo) {
                    if (isset($bcTodolist->todoitems[$mappedTodo['id']])) {
                        continue;
                    }
                    if ($type == 'tasks') {
                        $tasksUpdate[] = [
                            'action' => 'delete',
                            'item' => [
                                'type' => 'task',
                                'activity_id' => $activityId,
                                'person_id' => $mappedTodo['person_id'],
                                'task_id' => $id,
                            ],
                            'basecamp' => [
                                'todo_id' => $mappedTodo['id'],
                                'todolist_id' => $todolistId,
                            ],
                        ];
                    } else {
                        $tasksUpdate[] = [
                            'action' => 'delete',
                            'item' => [
                                'type' => 'person',
                                'activity_id' => $activityId,
                                'person_id' => $mappedTodo['person_id'],
                            ],
                            'basecamp' => [
                                'todo_id' => $mappedTodo['id'],
                                'todolist_id' => $todolistId,
                            ],
                        ];
                    }
                }
            }
        }

        if (!$tasksUpdate) {
            return;
        }

        $this->getUser->overrideCostlockerUser($projectRequest->costlockerUser);
        $response = $this->costlocker->__invoke("/projects", [
            'id' => $projectRequest->costlockerId,
            'items' => $tasksUpdate,
        ]);

        if ($response->getStatusCode() != 200) {
            $changelog->error = "Costlocked failed ({$response->getBody()})";
            return;
        }
        
        $createdTasks = json_decode($response->getBody(), true)['data'][0]['items'];

        foreach ($createdTasks as $index => $createdItem) {
            $ids = $createdItem['item'];
            $activityId = $ids['activity_id'];
            $changelog->initActivity($activityId, $tasksUpdate[$index]['basecamp']['todolist_id']);
            if ($createdItem['action'] == 'upsert') {
                $changelog->addTask($activityId, 'tasks', $ids['task_id'], [
                    'id' => $tasksUpdate[$index]['basecamp']['todo_id'],
                    'person_id' => $ids['person_id'],
                    'name' => $tasksUpdate[$index]['task'],
                    'isCreated' => true,
                ]);
                // existing person activity without task is automatically deleted in API
                if (isset($bcProject['activities'][$activityId]['persons'][$ids['person_id']])) {
                    $changelog->deleteTask($activityId, 'persons', $ids['person_id']);
                }
            } else {
                if ($ids['type'] == 'task') {
                    $changelog->deleteTask($activityId, 'tasks', $ids['task_id']);
                } else {
                    $changelog->deleteTask($activityId, 'persons', $ids['person_id']);
                }
            }
        }
    }

    private function findByBasecampId(array $data, $todolistId)
    {
        foreach ($data as $costlockerId => $mapping) {
            if ($mapping['id'] == $todolistId) {
                return $costlockerId;
            }
        }
    }

    private function updateMapping(array &$bcProject, SyncChangelog $changelog)
    {
        foreach ($changelog->createdActivities as $activityId => $activity) {
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
                    unset($mappedTodo['isCreated']);
                    $bcProject['activities'][$activityId][$type][$taskId] = [
                        'id' => $mappedTodo['id'],
                        'person_id' => $mappedTodo['person_id'],
                        'name' => $mappedTodo['name'],
                    ];
                }
            }
        }

        foreach ($changelog->deleteSummary['activities'] ?? [] as $activity) {
            unset($bcProject['activities'][$activity]);
        }
        foreach (['tasks', 'persons'] as $type) {
            foreach ($changelog->deleteSummary[$type] ?? [] as $activityId => $tasks) {
                foreach ($tasks as $taskId) {
                    unset($bcProject['activities'][$activityId][$type][$taskId]);
                }
            }
        }
        return $bcProject;
    }

    private function saveProject(array $bcProject, SyncResult $result)
    {
        $result->mappedProject = $this->database->upsertProject(
            $bcProject['costlocker_id'],
            [
                'id' => $bcProject['id'],
                'account' => $result->syncConfig->account,
                'activities' => $bcProject['activities'],
            ],
            $result->getSettings()
        );
    }
}
