<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\BasecampProject;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping = [];
    public $lastSettings;
    public $shouldRegisterWebhooks;

    public function upsertProject($costockerProjectId, array $update)
    {
        $this->mapping[$costockerProjectId] = $update;
        $this->lastSettings = $update['settings'];
        if ($this->shouldRegisterWebhooks) {
            return $this->stubBasecampProject($costockerProjectId, $update);
        }
    }

    public function findByCostlockerId($id)
    {
        if (isset($this->mapping[$id])) {
            return $this->stubBasecampProject($id, $this->mapping[$id]);
        }
    }

    public function findByBasecampId($id)
    {
        foreach ($this->mapping as $costlockerId => $mapping) {
            if ($id == $mapping['id']) {
                return $this->stubBasecampProject($costlockerId, $mapping);
            }
        }
    }

    private function stubBasecampProject($costlockerId, array $mapping)
    {
        $p = new BasecampProject();
        $p->basecampProject = $mapping['id'];
        $p->mapping = $mapping['activities'];
        $p->updateSettings($this->lastSettings);
        $p->basecampUser = new \Costlocker\Integrations\Entities\BasecampUser();
        $p->basecampUser->basecampAccount = new \Costlocker\Integrations\Entities\BasecampAccount();
        $p->costlockerProject = new \Costlocker\Integrations\Entities\CostlockerProject();
        $p->costlockerProject->id = $costlockerId;
        $p->costlockerProject->costlockerCompany = new \Costlocker\Integrations\Entities\CostlockerCompany();
        return $p;
    }
}
