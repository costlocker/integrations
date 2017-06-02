<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProject
{
    private $basecampFactory;

    private $costlocker;
    /** @var \Costlocker\Integrations\Basecamp\Api\BasecampApi */
    private $basecamp;

    public function __construct(CostlockerClient $c, BasecampFactory $b)
    {
        $this->costlocker = $c;
        $this->basecampFactory = $b;
    }

    public function __invoke(array $config)
    {
        $response = $this->costlocker->__invoke("/projects/{$config['costlockerProject']}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];
        list($people, $activities) = $this->analyzeProjectItems($project['items']);

        $this->basecamp = $this->basecampFactory->__invoke($config['account']);
        $bcProjectId = $this->createProject($project);
        $grantedPeople = $this->grantAccess($bcProjectId, $people);
        $todolists = $this->createTodolists($bcProjectId, $activities);

        return [
            'costlocker' => $project,
            'basecamp' => [
                'id' => $bcProjectId,
                'people' => $grantedPeople,
                'todolists' => $todolists,
            ],
        ];
    }

    private function analyzeProjectItems(array $projectItems)
    {
        $persons = [];
        $activities = [];
        foreach ($projectItems as $item) {
            if ($item['item']['type'] == 'activity') {
                $activities[$item['item']['activity_id']] = [
                    'name' => $item['activity']['name'],
                    'tasks' => [],
                ];
            }
            if ($item['item']['type'] == 'person') {
                $person = $item['person'];
                $persons[$person['email']] = "{$person['first_name']} {$person['last_name']}";
            }
            if (isset($item['hours']['is_aggregation']) && !$item['hours']['is_aggregation']) {
                if ($item['item']['type'] == 'person') {
                    $id = $item['item']['person_id'];
                    $task = $activities[$item['item']['activity_id']]['name'];
                } else {
                    $id = $item['item']['task_id'];
                    $task = $item['task']['name'];
                }
                $activities[$item['item']['activity_id']]['tasks'][$id] = $task;
            }
        }
        return [$persons, $activities];
    }

    private function createProject(array $project)
    {
        $name = "{$project['client']['name']} | {$project['name']}";
        return $this->basecamp->createProject($name, null, null);
    }

    private function grantAccess($bcProjectId, array $peopleFromCostlocker)
    {
        $peopleEmails = array();
        foreach ($peopleFromCostlocker as $email => $fullname) {
            $peopleEmails["{$fullname} ({$email})"] = $email;
        }
        $this->basecamp->grantAccess($bcProjectId, $peopleEmails);
        return $peopleEmails;
    }

    private function createTodolists($bcProjectId, array $activities)
    {
        $mapping = [];
        foreach ($activities as $activityId => $activity) {
            $bcTodolistId = $this->basecamp->createTodolist($bcProjectId, $activity['name']);
            $todos = [];
            foreach ($activity['tasks'] as $taskId => $task) {
                $todos[$taskId] = $this->basecamp->createTodo($bcProjectId, $bcTodolistId, $task, null);
            }
            $mapping[$activityId] = [
                'id' => $bcTodolistId,
                'todos' => $todos,
            ];
        }
        return $mapping;
    }
}
