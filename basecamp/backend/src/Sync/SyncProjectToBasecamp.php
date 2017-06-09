<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\CostlockerClient;
use Symfony\Component\HttpFoundation\ParameterBag;

class SyncProjectToBasecamp
{
    private $costlocker;
    private $synchronizer;

    public function __construct(CostlockerClient $c, BasecampFactory $b, SyncDatabase $db)
    {
        $this->costlocker = $c;
        $this->synchronizer = new Synchronizer($b, $db);
    }

    public function __invoke(array $jsonRequest)
    {
        $json = new ParameterBag($jsonRequest);

        $config = new SyncRequest();
        $config->account = $json->get('account');
        $config->costlockerProject = $json->get('costlockerProject');
        $isProjectLinked = $json->get('mode') == 'add';
        $config->updatedBasecampProject = $isProjectLinked ? $json->get('basecampProject') : null;
        $config->areTodosEnabled = $json->get('areTodosEnabled');
        if ($config->areTodosEnabled) {
            $config->isDeletingTodosEnabled = $json->get('isDeletingTodosEnabled');
            $config->isRevokeAccessEnabled = $json->get('isRevokeAccessEnabled');
        }

        $project = $this->findProjectInCostlocker($config->costlockerProject);

        $r = new SyncProjectRequest();
        $r->costlockerId = $project['id'];
        $r->projectItems = $project['items'];
        $r->isCompleteProjectSynchronized = true;
        $r->createProject = function ($createBasecampProject) use ($project, $config) {
            $projectId = $project['project_id']['id'] ?? null;
            $name =
                "{$project['client']['name']} | {$project['name']}" .
                ($projectId ? " [{$projectId}]" : '');
            return $config->updatedBasecampProject ?: $createBasecampProject($name);
        };

        return [$this->synchronizer->__invoke($r, $config)];
    }

    private function findProjectInCostlocker($costlockerId)
    {
        $response = $this->costlocker->__invoke("/projects/{$costlockerId}?types=peoplecosts");
        return json_decode($response->getBody(), true)['data'];
    }
}
