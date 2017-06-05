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

        return [
            'costlocker' => $project,
            'basecamp' => [
                'id' => $bcProjectId,
                'people' => $grantedPeople,
                'activities' => $todolists,
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
                if ($item['item']['type'] == 'person') {
                    $id = $item['item']['person_id'];
                    $task = [
                        'id' => $id,
                        'name' => $activities[$item['item']['activity_id']]['name'],
                        'email' => $item['person']['email'],
                    ];
                } else {
                    $id = $item['item']['task_id'];
                    $task = [
                        'id' => $id,
                        'name' => $item['task']['name'],
                        'email' => $personsMap[$item['item']['person_id']],
                    ];
                }
                $activities[$item['item']['activity_id']]['tasks'][$id] = $task;
            }
        }
        return [$persons, array_reverse($activities, true)];
    }

    private function upsertProject(array $project)
    {
        if (array_key_exists($project['id'], $this->database)) {
            return $this->database[$project['id']];
        }
        $name = "{$project['client']['name']} | {$project['name']}";
        return [
            'id' => $this->basecamp->createProject($name, null, null),
            'activities' => [],
        ];
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

    private function createTodolists(array $bcProject, array $activities)
    {
        $mapping = [];
        foreach ($activities as $activityId => $activity) {
            $bcTodolist = $this->upsertTodolist($bcProject, $activity);
            $todos = [];
            foreach ($activity['tasks'] as $taskId => $task) {
                $todos[$taskId] = $this->upsertTodo($bcProject, $bcTodolist, $task);
            }
            $mapping[$activityId] = [
                'id' => $bcTodolist['id'],
                'todos' => $todos,
            ];
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
            'todos' => [],
        ];
    }

    private function upsertTodo(array $bcProject, array $bcTodolist, array $task)
    {
        if (array_key_exists($task['id'], $bcTodolist['todos'])) {
            return $bcTodolist['todos'][$task['id']];
        }
        $assignee = $bcProject['basecampPeople'][$task['email']]->id;
        return $this->basecamp->createTodo($bcProject['id'], $bcTodolist['id'], $task['name'], $assignee);
    }
}
