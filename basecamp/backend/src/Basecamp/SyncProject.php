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
        $bcPeople = $this->basecamp->getPeople($bcProjectId);
        $todolists = $this->createTodolists($bcProjectId, $bcPeople, $activities);

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
        $personsMap = [];
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
                $personsMap[$item['item']['person_id']] = $person['email'];
            }
            if (isset($item['hours']['is_aggregation']) && !$item['hours']['is_aggregation']) {
                if ($item['item']['type'] == 'person') {
                    $id = $item['item']['person_id'];
                    $task = [
                        'name' => $activities[$item['item']['activity_id']]['name'],
                        'email' => $item['person']['email'],
                    ];
                } else {
                    $id = $item['item']['task_id'];
                    $task = [
                        'name' => $item['task']['name'],
                        'email' => $personsMap[$item['item']['person_id']],
                    ];
                }
                $activities[$item['item']['activity_id']]['tasks'][$id] = $task;
            }
        }
        return [$persons, array_reverse($activities, true)];
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

    private function createTodolists($bcProjectId, array $bcPeople, array $activities)
    {
        $mapping = [];
        foreach ($activities as $activityId => $activity) {
            $bcTodolistId = $this->basecamp->createTodolist($bcProjectId, $activity['name']);
            $todos = [];
            foreach ($activity['tasks'] as $taskId => $task) {
                $assignee = $bcPeople[$task['email']]->id;
                $todos[$taskId] = $this->basecamp->createTodo($bcProjectId, $bcTodolistId, $task['name'], $assignee);
            }
            $mapping[$activityId] = [
                'id' => $bcTodolistId,
                'todos' => $todos,
            ];
        }
        return $mapping;
    }
}
