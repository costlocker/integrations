<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Sync\Connect\Costlocker;
use Costlocker\Integrations\Sync\Connect\Basecamp;

class Synchronizer
{
    private $costlocker;
    private $basecamp;
    private $database;

    public function __construct(Costlocker $c, Basecamp $b, SyncDatabase $db)
    {
        $this->costlocker = $c;
        $this->basecamp = $b;
        $this->database = $db;
    }

    public function __invoke(SyncRequest $r)
    {
        $this->basecamp->init($r);
        $this->costlocker->init($r);

        $result = new SyncResponse($r);
        $isNotMapped = $this->upsertProject($r);

        if ($isNotMapped) {
            $result->basecampChangelog->error = "{$r->costlockerId} is not mapped";
            return $result;
        }

        list($people, $activities) = $this->analyzeProjectItems($r);

        $this->basecamp->addBasecampProjectStatus($result->basecampChangelog);
        $result->costlockerChangelog->projectId = $r->costlockerId;

        if ($this->basecamp->isDeleted()) {
            $result->basecampChangelog->error =
                "Project {$result->basecampChangelog->projectId} is not available in Basecamp";
            $result->mappedProject = $this->database->findByCostlockerId($r->costlockerId);
            return $result;
        }

        if ($r->settings->areTodosEnabled) {
            $this->grantAccess($people, $result->basecampChangelog);
            $this->createTodolists($activities, $result->basecampChangelog);
            $this->deleteLegacyEntitiesInBasecamp($people, $activities, $r, $result->basecampChangelog);
            $this->basecamp->applyChanges($result->basecampChangelog);
        }

        if ($r->settings->areTasksEnabled) {
            if ($this->basecamp->canBeSynchronizedFromBasecamp()) {
                $this->synchronizePeopleCosts($r->settings, $result->costlockerChangelog);
                $this->basecamp->applyChanges($result->costlockerChangelog);
            } else {
                $r->settings->areTasksEnabled = false;
            }
        }

        $this->saveProject($result);

        return $result;
    }

    private function upsertProject(SyncRequest $r)
    {
        $costlockerProject = $r->isCompleteProjectSynchronized
            ? $this->costlocker->loadProjectFromCostlocker($r->costlockerId) : null;

        $existingProject = $this->database->findByCostlockerId($r->costlockerId);
        if ($existingProject) {
            $this->basecamp->useExistingProject($existingProject);
            return;
        }

        if (!$r->isCompleteProjectSynchronized) {
            // creating new project in webhook is not supported
            return true;
        }

        $customProjectId = $costlockerProject['project_id']['id'] ?? null;
        $name =
            "{$costlockerProject['client']['name']} | {$costlockerProject['name']}" .
            ($customProjectId ? " [{$customProjectId}]" : '');
        $this->basecamp->useNewProject($r->updatedBasecampProject, $name, $r->basecampClassicCompanyId);
    }

    private function analyzeProjectItems(SyncRequest $request)
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
            foreach ($this->basecamp->getMappedActivities() as $activityId => $activity) {
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

    private function grantAccess(array $peopleFromCostlocker, SyncChangelog $changelog)
    {
        $peopleEmails = [];
        foreach ($peopleFromCostlocker as $email => $fullname) {
            $peopleEmails["{$fullname} ({$email})"] = $email;
        }
        if ($peopleEmails) {
            $this->basecamp->grantAccess($peopleEmails);
            $changelog->grantAccess($peopleEmails);
        }
    }

    private function createTodolists(array $activities, SyncChangelog $changelog)
    {
        foreach ($activities as $activityId => $activity) {
            if ($activity['isDeleted']) {
                continue;
            }
            $bcTodolist = $this->basecamp->upsertTodolist($activity);
            $changelog->initActivity($activityId, $bcTodolist['id'], $bcTodolist['isCreated']);
            foreach (['tasks', 'persons'] as $type) {
                foreach ($activity['upsert'][$type] as $id => $task) {
                    $todo = $this->basecamp->upsertTodo($bcTodolist, $task);
                    if (!$todo['isCreated']) {
                        continue;
                    }
                    $changelog->addTask($activityId, $type, $id, $todo);
                }
            }
        }
    }

    private function deleteLegacyEntitiesInBasecamp(
        array $peopleFromCostlocker, array $activities, SyncRequest $request, SyncChangelog $changelog
    ) {
        if ($this->basecamp->isCreated() || $request->settings->isDeleteDisabledInCostlocker()) {
            return;
        }

        if ($request->settings->isDeletingTodosEnabled) {
            foreach ($activities as $activityId => $activity) {
                foreach (['tasks', 'persons'] as $type) {
                    foreach ($activity['delete'][$type] as $id => $task) {
                        $this->basecamp->deleteTodo($activityId, $type, $id);
                        $changelog->deleteTask($activityId, $type, $id);
                    }
                }
                if ($activity['isDeleted']) {
                    $this->basecamp->deleteTodolist($activityId);
                    $changelog->deleteActivity($activityId);
                }
            }
        }

        if ($request->isCompleteProjectSynchronized && $request->settings->isRevokeAccessEnabled) {
            $assignedIds = $this->basecamp->getAssignedIds();

            foreach ($this->basecamp->getBasecampPeople() as $email => $bcPerson) {
                if (!$bcPerson->admin &&
                    !array_key_exists($email, $peopleFromCostlocker) &&
                    !array_key_exists($bcPerson->id, $assignedIds)) {
                    $this->basecamp->revokeAccessToPerson($bcPerson);
                    $changelog->revokeAccess($email);
                }
            }
        }
    }

    private function synchronizePeopleCosts(SyncSettings $settings, SyncChangelog $changelog)
    {
        if ($this->basecamp->isCreated()) {
            return;
        }

        $tasksUpdate = [];
        $newActitivies = [];
        foreach ($this->basecamp->getTodolists() as $todolistId => $bcTodolist) {
            $activityId = $this->basecamp->findMappedActivity($todolistId);
            if (!$activityId && $settings->isCreatingActivitiesEnabled) {
                $activityId = $this->costlocker->findExistingActivity($bcTodolist->name);
                $newActitivies[] = $activityId;
            }
            if (!$activityId) {
                continue;
            }
            foreach ($bcTodolist->todoitems as $todoId => $todo) {
                if ($this->basecamp->isTodoMapped($activityId, $todoId) || !$todo->assignee) {
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

            if (!$settings->isDeletingTasksEnabled) {
                continue;
            }
            foreach (['tasks', 'persons'] as $type) {
                foreach ($this->basecamp->getMappedTasks($activityId, $type) as $id => $mappedTodo) {
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

        if ($settings->isDeletingActivitiesEnabled) {
            foreach ($this->basecamp->getDeletedActivities() as $activityId => $todolistId) {
                $tasksUpdate[] = [
                    'action' => 'delete',
                    'item' => [
                        'type' => 'activity',
                        'activity_id' => $activityId,
                    ],
                    'basecamp' => [
                        'todolist_id' => $todolistId,
                    ],
                ];
            }
        }

        if (!$tasksUpdate) {
            return;
        }

        list($hasFailed, $response) = $this->costlocker->updateProject($tasksUpdate);

        if ($hasFailed) {
            $changelog->error = "Costlocked failed ({$response})";
            return;
        }

        foreach ($response as $index => $createdItem) {
            $ids = $createdItem['item'];
            $activityId = $ids['activity_id'];
            $changelog->initActivity(
                $activityId,
                $tasksUpdate[$index]['basecamp']['todolist_id'],
                in_array($activityId, $newActitivies)
            );
            if ($createdItem['action'] == 'upsert') {
                $changelog->addTask($activityId, 'tasks', $ids['task_id'], [
                    'id' => $tasksUpdate[$index]['basecamp']['todo_id'],
                    'person_id' => $ids['person_id'],
                    'name' => $tasksUpdate[$index]['task'],
                    'isCreated' => true,
                ]);
                // existing person activity without task is automatically deleted in API
                if (isset($this->basecamp->getMappedTasks($activityId, 'persons')[$ids['person_id']])) {
                    $changelog->deleteTask($activityId, 'persons', $ids['person_id']);
                }
            } else {
                if ($ids['type'] == 'activity') {
                    $changelog->deleteActivity($activityId);
                } elseif ($ids['type'] == 'task') {
                    $changelog->deleteTask($activityId, 'tasks', $ids['task_id']);
                } else {
                    $changelog->deleteTask($activityId, 'persons', $ids['person_id']);
                }
            }
        }
    }

    private function saveProject(SyncResponse $result)
    {
        $result->newMapping = $this->basecamp->getMappedActivities();
        $result->mappedProject = $this->database->upsertProject($result);

        if ($result->request->isCompleteProjectSynchronized && $result->mappedProject) {
            try {
                $this->costlocker->registerWebhook($result->mappedProject);
                $this->basecamp->registerWebhook($result->mappedProject);
            } catch (\Exception $e) {
                $result->webhookError = $e->getMessage();
            }
        }
    }
}
