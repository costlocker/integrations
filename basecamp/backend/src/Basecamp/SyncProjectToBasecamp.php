<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProjectToBasecamp
{
    private $costlocker;
    private $synchronizer;

    public function __construct(CostlockerClient $c, BasecampFactory $b, SyncDatabase $db)
    {
        $this->costlocker = $c;
        $this->synchronizer = new Synchronizer($b, $db);
    }

    public function __invoke(SyncRequest $config)
    {
        $project = $this->findProjectInCostlocker($config->costlockerProject);

        $r = new SyncProjectRequest();
        $r->costlockerId = $project['id'];
        $r->projectItems = $project['items'];
        $r->isCompleteProjectSynchronized = true;
        $r->createProject = function ($createBasecampProject) use ($project, $config) {
            $name = "{$project['client']['name']} | {$project['name']}";
            return [
                'id' => $config->updatedBasecampProject ?: $createBasecampProject($name),
                'costlocker_id' => $project['id'],
                'activities' => [],
                'isCreated' => true
            ];
        };

        return $this->synchronizer->__invoke($r, $config);
    }

    private function findProjectInCostlocker($costlockerId)
    {
        $response = $this->costlocker->__invoke("/projects/{$costlockerId}?types=peoplecosts");
        return json_decode($response->getBody(), true)['data'];
    }
}
