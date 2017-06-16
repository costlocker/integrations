<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Basecamp\Api\BasecampException;
use Costlocker\Integrations\Entities\BasecampProject;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class SynchronizedBasecamp
{
    private $factory;
    private $client;
    private $logger;
    private $webhookUrl;

    private $bcProject;
    private $todolists;

    public function __construct(BasecampFactory $b, EventsLogger $l, $webhookUrl)
    {
        $this->factory = $b;
        $this->logger = $l;
        $this->webhookUrl = $webhookUrl;
    }

    public function init($basecampAccount)
    {
        $this->client = $this->factory->__invoke($basecampAccount);
        $this->bcProject = null;
        $this->todolists = null;
    }

    public function useNewProject($existingBasecampId, $projectName, $basecampClassicCompanyId)
    {
        $this->bcProject = [
            'id' => $existingBasecampId
                ?: $this->client->createProject($projectName, $basecampClassicCompanyId, null),
            'activities' => [],
            'isCreated' => true
        ];
    }

    public function useExistingProject(array $existingProject)
    {
        $this->bcProject = ['isCreated' => false] + $existingProject;
    }

    public function getMappedActivities()
    {
        return $this->bcProject['activities'];
    }

    public function addBasecampProjectStatus(SyncChangelog $changelog)
    {
        $changelog->projectId = $this->bcProject['id'];
        $changelog->isCreated = $this->bcProject['isCreated'];
    }

    public function isDeleted()
    {
        if ($this->bcProject['isCreated']) {
            return;
        }
        try {
            $this->client->projectExists($this->bcProject['id']);
        } catch (BasecampException $e) {
            return true;
        }
    }

    public function isCreated()
    {
        return $this->bcProject['isCreated'];
    }

    public function canBeSynchronizedFromBasecamp()
    {
        return $this->client->canBeSynchronizedFromBasecamp();
    }

    public function getBasecampPeople()
    {
        if (!array_key_exists('basecampPeople', $this->bcProject)) {
            $this->bcProject['basecampPeople'] = $this->client->getPeople($this->bcProject['id']);
        }
        return $this->bcProject['basecampPeople'];
    }

    public function getTodolists()
    {
        if ($this->todolists === null) {
            $this->todolists = $this->client->getTodolists($this->bcProject['id']);
        }
        return $this->todolists;
    }

    public function getAssignedIds()
    {
        $assignedIds = [];
        foreach ($this->getTodolists() as $todolist) {
            foreach ($todolist->todoitems as $todoitem) {
                $assignedIds[$todoitem->assignee_id] = $todoitem->assignee_id;
            }
        }
        return $assignedIds;
    }

    public function getMappedTasks($activityId, $type)
    {
        return $this->bcProject['activities'][$activityId][$type] ?? [];
    }

    public function findMappedActivity($todolistId)
    {
        return $this->findByBasecampId($this->bcProject['activities'], $todolistId);
    }

    public function isTodoMapped($activityId, $bcTodoId)
    {
        return 
            $this->findByBasecampId($this->getMappedTasks($activityId, 'tasks'), $bcTodoId) ||
            $this->findByBasecampId($this->getMappedTasks($activityId, 'persons'), $bcTodoId);
    }

    private function findByBasecampId(array $data, $todolistId)
    {
        foreach ($data as $costlockerId => $mapping) {
            if ($mapping['id'] == $todolistId) {
                return $costlockerId;
            }
        }
    }

    public function getDeletedActivities()
    {
        $bcTodolists = $this->getTodolists();
        foreach ($this->bcProject['activities'] as $activityId => $activity) {
            $todolistId = $activity['id'];
            if (isset($bcTodolists[$todolistId])) {
                continue;
            }
            yield $activityId => $todolistId;
        }
    }

    public function upsertTodolist(array $activity)
    {
        if (array_key_exists($activity['id'], $this->bcProject['activities'])) {
            return $this->bcProject['activities'][$activity['id']] + ['isCreated' => false];
        }
        return [
            'id' => $this->client->createTodolist($this->bcProject['id'], $activity['name']),
            'tasks' => [],
            'persons' => [],
            'isCreated' => true,
        ];
    }

    public function upsertTodo(array $bcTodolist, array $task)
    {
        if (array_key_exists($task['task_id'], $bcTodolist['tasks'])) {
            return $bcTodolist['tasks'][$task['task_id']] + ['isCreated' => false];
        }
        if (!$task['task_id'] && array_key_exists($task['person_id'], $bcTodolist['persons'])) {
            return $bcTodolist['persons'][$task['person_id']] + ['isCreated' => false];
        }
        $assignee = $this->getBasecampPeople()[$task['email']]->id;
        return [
            'id' => $this->client->createTodo($this->bcProject['id'], $bcTodolist['id'], $task['name'], $assignee),
            'person_id' => $task['person_id'],
            'name' => $task['name'],
            'isCreated' => true,
        ];
    }

    public function deleteTodolist($activityId)
    {
        $bcTodolistId = $this->bcProject['activities'][$activityId]['id'];

        $this->getTodolists();
        if (isset($this->todolists[$bcTodolistId]) && !$this->todolists[$bcTodolistId]->todoitems) {
            $this->client->deleteTodolist($this->bcProject['id'], $bcTodolistId);
        }
    }

    public function deleteTodo($activityId, $type, $personOrTaskId)
    {
        $bcTodolistId = $this->bcProject['activities'][$activityId]['id'];
        $bcTodoId = $this->bcProject['activities'][$activityId][$type][$personOrTaskId]['id'];

        $this->getTodolists();
        if (
            isset($this->todolists[$bcTodolistId]) &&
            array_key_exists($bcTodoId, $this->todolists[$bcTodolistId]->todoitems)
        ) {
            $this->client->deleteTodo($this->bcProject['id'], $bcTodoId);
            unset($this->todolists[$bcTodolistId]->todoitems[$bcTodoId]);
        }
    }

    public function grantAccess($peopleEmails)
    {
        $this->client->grantAccess($this->bcProject['id'], $peopleEmails);;
    }

    public function revokeAccessToPerson(\stdClass $bcPerson)
    {
        $this->client->revokeAccess($this->bcProject['id'], $bcPerson->id);
    }

    public function applyChanges(SyncChangelog $changelog)
    {
        foreach ($changelog->getChangedActivities() as $activityId => $activity) {
            if (!array_key_exists($activityId, $this->bcProject['activities'])) {
                $this->bcProject['activities'][$activityId] = [];
            }
            $this->bcProject['activities'][$activityId] += [
                'id' => $activity['id'],
                'tasks' => [],
                'persons' => [],
            ];
            foreach (['tasks', 'persons'] as $type) {
                foreach ($activity[$type] as $taskId => $mappedTodo) {
                    unset($mappedTodo['isCreated']);
                    $this->bcProject['activities'][$activityId][$type][$taskId] = [
                        'id' => $mappedTodo['id'],
                        'person_id' => $mappedTodo['person_id'],
                        'name' => $mappedTodo['name'],
                    ];
                }
            }
        }

        foreach ($changelog->getDeleted('activities') as $activity) {
            unset($this->bcProject['activities'][$activity]);
        }

        foreach (['tasks', 'persons'] as $type) {
            foreach ($changelog->getDeleted($type) as $activityId => $tasks) {
                foreach ($tasks as $taskId) {
                    unset($this->bcProject['activities'][$activityId][$type][$taskId]);
                }
            }
        }
    }

    public function registerWebhook(BasecampProject $project)
    {
        if ($project->basecampWebhook && $project->isNotChangedSetting('isBasecampWebhookEnabled')) {
            return;
        }

        $project->basecampWebhook = $this->client->registerWebhook(
            $project->basecampProject,
            $this->webhookUrl,
            $project->settings['isBasecampWebhookEnabled'],
            $project->basecampWebhook
        );

        $this->logger->__invoke(
            Event::REGISTER_BASECAMP_WEBHOOK,
            ['webhook' => $project->basecampWebhook],
            $project
        );
    }
}
