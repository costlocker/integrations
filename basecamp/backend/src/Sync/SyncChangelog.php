<?php

namespace Costlocker\Integrations\Sync;

class SyncChangelog
{
    public $projectId;
    public $isCreated = false;
    public $error;

    private $grantedPeople = [];
    private $changedActivities = [];
    private $deleteSummary = [
        'activities' => [], // <activity_id> => <activity_id>
        'tasks' => [],      // <activity_id> => [<task_id> => <task_id>]
        'persons' => [],    // <activity_id> => [<person_id> => <person_id>]
        'revoked' => [],    // <email>       => <email>
    ];

    public function initActivity($activityId, $bcTodolistId, $siCreated = false)
    {
        if (!array_key_exists($activityId, $this->changedActivities)) {
            $this->changedActivities[$activityId] = [
                'id' => $bcTodolistId,
                'isCreated' => $siCreated,
                'tasks' => [],
                'persons' => [],
            ];
        }
    }

    public function addTask($activityId, $type, $personOrTaskId, array $task)
    {
        $this->changedActivities[$activityId][$type][$personOrTaskId] = $task;
    }

    public function deleteActivity($activityId)
    {
        $this->deleteSummary['activities'][$activityId] = $activityId;
    }

    public function deleteTask($activityId, $type, $personOrTaskId)
    {
        $this->deleteSummary[$type][$activityId][$personOrTaskId] = $personOrTaskId;
    }

    public function getDeleted($type)
    {
        return $this->deleteSummary[$type] ?? [];
    }

    public function grantAccess(array $peopleEmails)
    {
        $this->grantedPeople = $peopleEmails;
    }

    public function revokeAccess($email)
    {
        $this->deleteSummary['revoked'][$email] = $email;
    }

    public function wasSomethingChanged()
    {
        return $this->isCreated || $this->getChangedActivities() || $this->wasSomethingDeleted();
    }

    public function getChangedActivities()
    {
        $activities = [];
        foreach ($this->changedActivities as $activityId => $activity) {
            if ($this->isActivityChanged($activityId)) {
                $activities[$activityId] = $activity;
            }
        }
        return $activities;
    }

    private function isActivityChanged($activityId)
    {
        return $this->changedActivities[$activityId]['isCreated']
            || $this->changedActivities[$activityId]['tasks']
            || $this->changedActivities[$activityId]['persons'];
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

    public function toArray()
    {
        return [
            'id' => $this->projectId,
            'isCreated' => $this->isCreated,
            'people' => $this->grantedPeople,
            'activities' => $this->getChangedActivities(),
            'delete' => $this->deleteSummary,
            'error' => $this->error,
        ];
    }

    public static function arrayToStats($type, array $results)
    {
        if (!$results) {
            return null;
        }

        $stats = [
            'system' => $type,
            'project' => [
                'id' => $results['id'],
                'createdCount' => (int) ($results['isCreated'] ?? false),
            ],
            'activities' => [
                'createdCount' => 0,
                'deletedCount' => count($results['delete']['activities']),
            ],
            'tasks' => [
                'createdCount' => 0,
                'deletedCount' => 0,
            ],
            'people' => [
                'revokedCount' => count($results['delete']['revoked']),
            ],
        ];
        foreach ($results['activities'] ?? [] as $activity) {
            $stats['activities']['createdCount'] += (bool) ($activity['isCreated'] ?? false);
            foreach (['tasks', 'persons'] as $type) {
                foreach ($activity[$type] as $task) {
                    $stats['tasks']['createdCount'] += (bool) ($task['isCreated'] ?? false);
                }
            }
        }
        foreach (['tasks', 'persons'] as $type) {
            foreach ($results['delete'][$type] as $activityTasks) {
                $stats['tasks']['deletedCount'] += count($activityTasks);
            }
        }

        $totalCounts =
            $stats['project']['createdCount'] +
            $stats['activities']['createdCount'] +
            $stats['activities']['deletedCount'] +
            $stats['tasks']['createdCount'] +
            $stats['tasks']['deletedCount'] +
            $stats['people']['revokedCount'];

        return $totalCounts ? $stats : null;
    }
}
