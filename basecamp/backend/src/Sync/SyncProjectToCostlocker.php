<?php

namespace Costlocker\Integrations\Sync;

class SyncProjectToCostlocker
{
    private $synchronizer;

    public function __construct(Synchronizer $s)
    {
        $this->synchronizer = $s;
    }

    public function __invoke(array $json)
    {
        $costlockerId = $json['costlockerProject'];
        $project = $this->synchronizer->findBasecampProjectByCostlockerId($costlockerId);

        if (!$project) {
            return [];
        }

        $r = new SyncProjectRequest();
        $r->costlockerId = $costlockerId;
        $r->projectItems = []; // costlocker -> basecamp is disabled
        $r->isCompleteProjectSynchronized = false;
        $r->costlockerUser = $project->costlockerProject->costlockerCompany->defaultCostlockerUser;

        $config = new SyncRequest();
        $config->costlockerProject = $costlockerId;
        $config->account = $project->basecampUser->id;
        $options = [
            'areTasksEnabled', 'isDeletingTasksEnabled', 'isCreatingActivitiesEnabled',
            'isDeletingActivitiesEnabled', 'isBasecampWebhookEnabled'
        ];
        foreach ($options as $option) {
            if (array_key_exists($option, $project->settings)) {
                $config->{$option} = $project->settings[$option];
            }
        }

        return [$this->synchronizer->__invoke($r, $config)];
    }
}
