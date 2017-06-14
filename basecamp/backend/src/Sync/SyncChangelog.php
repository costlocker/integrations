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

    public function wasSomethingChanged()
    {
        return $this->wasProjectCreated || $this->getcChangedActivities() || $this->wasSomethingDeleted();
    }

    public function getcChangedActivities()
    {
        $activities = [];
        foreach ($this->createdActivities as $activityId => $activity) {
            if ($this->isActivityChanged($activityId)) {
                $activities[$activityId] = $activity;
            }
        }
        return $activities;
    }

    private function isActivityChanged($activityId)
    {
        return $this->createdActivities[$activityId]['isCreated']
            || $this->createdActivities[$activityId]['tasks']
            || $this->createdActivities[$activityId]['persons'];
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
            'activities' => $this->getcChangedActivities(),
            'delete' => $this->deleteSummary,
            'error' => $this->error,
        ];
    }
}
