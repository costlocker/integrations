<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Basecamp\Api\BasecampException;

class Synchronizer
{
    private $basecampFactory;

    /** @var \Costlocker\Integrations\Basecamp\Api\BasecampApi */
    private $basecamp;
    private $database;

    public function __construct(BasecampFactory $b, SyncDatabase $db)
    {
        $this->basecampFactory = $b;
        $this->database = $db;
    }

    public function __invoke(SyncProjectRequest $r, SyncRequest $config)
    {
        $result = new SyncResult($r, $config);
        $this->basecamp = $this->basecampFactory->__invoke($config->account);
        $bcProject = $this->upsertProject($r);

        if (!$bcProject) {
            $result->error = "{$r->costlockerId} is not mapped";
            return $result;
        }

        list($people, $activities) = $this->analyzeProjectItems($bcProject, $r);

        $result->basecampProjectId = $bcProject['id'];
        $result->wasProjectCreated = $bcProject['isCreated'];

        if ($this->checkDeletedProject($bcProject)) {
            $result->error = "Project was deleted in Basecamp";
            $result->mappedProject = $this->database->findBasecampProject($r->costlockerId);
            return $result;
        }

        if ($config->areTodosEnabled) {
            $result->grantedPeople = $this->grantAccess($result->basecampProjectId, $people);
            $bcProject['basecampPeople'] = $this->basecamp->getPeople($result->basecampProjectId);
            $result->todolists = $this->createTodolists($bcProject, $activities);
            $result->deleteSummary = $this->deleteLegacyEntitiesInBasecamp($bcProject, $people, $activities, $config);
        }

        $this->updateMapping($bcProject, $result);

        return $result;
    }

    private function upsertProject(SyncProjectRequest $r)
    {
        $existingProject = $this->database->findProject($r->costlockerId);
        if ($existingProject) {
            return ['isCreated' => false, 'costlocker_id' => $r->costlockerId] + $existingProject;
        }
        $projectId = $r->createProject->__invoke(
            function ($name) {
                return $this->basecamp->createProject($name, null, null);
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
                        'email' => $personsMap[$item['item']['person_id']],
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
            if ($activity['isDeleted']) {
                continue;
            }
            $bcTodolist = $this->upsertTodolist($bcProject, $activity);
            $todos = [
                'tasks' => [],
                'persons' => [],
            ];
            foreach ($activity['upsert']['tasks'] as $id => $task) {
                $todos['tasks'][$id] = $this->upsertTodo($bcProject, $bcTodolist, $task);
            }
            foreach ($activity['upsert']['persons'] as $id => $personWithoutTasks) {
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

    private function deleteLegacyEntitiesInBasecamp(
        array $bcProject, array $peopleFromCostlocker, array $activities, SyncRequest $config
    ) {
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
            foreach ($activities as $activityId => $activity) {
                $bcTodolistId = $bcProject['activities'][$activityId]['id'] ?? null;
                foreach (['tasks', 'persons'] as $type) {
                    foreach ($activity['delete'][$type] as $id => $task) {
                        $bcTodoId = $bcProject['activities'][$activityId][$type][$id]['id'];
                        if (isset($bcTodolists[$bcTodolistId]) && array_key_exists($bcTodoId, $bcTodolists[$bcTodolistId]->todoitems)) {
                            $this->basecamp->deleteTodo($bcProject['id'], $bcTodoId);
                            unset($bcTodolists[$bcTodolistId]->todoitems[$bcTodoId]);
                        }
                        $deleted[$type][$activityId][$id] = $id;
                    }
                }
                if ($activity['isDeleted']) {
                    if (isset($bcTodolists[$bcTodolistId]) && !$bcTodolists[$bcTodolistId]->todoitems) {
                        $this->basecamp->deleteTodolist($bcProject['id'], $bcTodolistId);
                    }
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

    private function updateMapping(array $bcProject, SyncResult $result)
    {
        foreach ($result->todolists as $activityId => $activity) {
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

        foreach ($result->deleteSummary['activities'] ?? [] as $activity) {
            unset($bcProject['activities'][$activity]);
        }
        foreach (['tasks', 'persons'] as $type) {
            foreach ($result->deleteSummary[$type] ?? [] as $activityId => $tasks) {
                foreach ($tasks as $taskId) {
                    unset($bcProject['activities'][$activityId][$type][$taskId]);
                }
            }
        }

        $result->mappedProject = $this->database->upsertProject(
            $bcProject['costlocker_id'],
            [
                'id' => $bcProject['id'],
                'account' => $result->syncConfig->account,
                'activities' => $bcProject['activities'],
            ],
            $result->settings
        );
    }
}
