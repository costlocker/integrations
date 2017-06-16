<?php

namespace Costlocker\Integrations\Sync;

class ProcessAggregatedBasecampWebhook
{
    private $database;
    private $synchronizer;

    public function __construct(SyncDatabase $db, Synchronizer $s)
    {
        $this->database = $db;
        $this->synchronizer = $s;
    }

    public function __invoke(array $json)
    {
        $costlockerId = $json['costlockerProject'];
        $project = $this->database->findByCostlockerId($costlockerId);

        if (!$project) {
            return [];
        }

        $r = new SyncRequest();
        $r->costlockerId = $costlockerId;
        $r->projectItems = []; // costlocker -> basecamp is disabled
        $r->isCompleteProjectSynchronized = false;
        $r->costlockerUser = $project->costlockerProject->costlockerCompany->defaultCostlockerUser;
        $r->account = $project->basecampUser->id;
        $r->settings->loadBasecampSettings($project->settings);
        $r->settings->disableUpdatingBasecamp();

        return [$this->synchronizer->__invoke($r)];
    }
}
