<?php

namespace Costlocker\Integrations\Sync;

class SyncChangelog
{
    public $wasProjectCreated = false;
    public $grantedPeople = [];
    public $createdActivities = [];
    public $deleteSummary = [
        'activities' => [], // <activity_id> => <activity_id>
        'tasks' => [],      // <activity_id> => [<task_id> => <task_id>]
        'persons' => [],    // <activity_id> => [<person_id> => <person_id>]
        'revoked' => [],    // <email>       => <email>
    ];
    public $error;

    public function initActivity($activityId, $bcTodolistId, $siCreated = false)
    {
        if (!array_key_exists($activityId, $this->createdActivities)) {
            $this->createdActivities[$activityId] = [
                'id' => $bcTodolistId,
                'isCreated' => $siCreated,
                'tasks' => [],
                'persons' => [],
            ];
        }
    }

    public function addTask($activityId, $type, $personOrTaskId, array $task)
    {
        $this->createdActivities[$activityId][$type][$personOrTaskId] = $task;
    }

    public function deleteActivity($activityId)
    {
        $this->deleteSummary['activities'][$activityId] = $activityId;
    }

    public function deleteTask($activityId, $type, $personOrTaskId)
    {
        $this->deleteSummary[$type][$activityId][$personOrTaskId] = $personOrTaskId;
    }

    public function revokeAccess($email)
    {
        $this->deleteSummary['revoked'][$email] = $email;
    }

    public function isActivityChanged($activityId)
    {
        return $this->createdActivities[$activityId]['isCreated']
            || $this->createdActivities[$activityId]['tasks']
            || $this->createdActivities[$activityId]['persons'];
    }

    public function wasSomethingChanged()
    {
        return $this->wasProjectCreated || $this->createdActivities || $this->wasSomethingDeleted();
    }

    private function wasSomethingDeleted()
    {
        foreach ($this->deleteSummary as $deleted) {
            if ($deleted) {
                return true;
            }
        }
        return false;
    }

    public function toArray($id)
    {
        return [
            'id' => $id,
            'wasProjectCreated' => $this->wasProjectCreated,
            'people' => $this->grantedPeople,
            'activities' => $this->createdActivities,
            'delete' => $this->deleteSummary,
            'error' => $this->error,
        ];
    }
}
