<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\BasecampUser;
use Costlocker\Integrations\Entities\BasecampProject;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping = [];
    public $company;
    public $lastSettings;
    public $shouldRegisterWebhooks;

    public function __construct()
    {
        $this->company = new CostlockerCompany();
        $this->company->defaultBasecampUser = new BasecampUser();
        $this->company->defaultCostlockerUser = new CostlockerUser();
    }

    public function upsertProject(SyncResponse $result)
    {
        $update = [
            'id' => $result->basecampChangelog->projectId,
            'account' => $result->request->account,
            'activities' => $result->newMapping,
        ];
        $this->mapping[$result->costlockerChangelog->projectId] = $update;
        $this->lastSettings = $result->getSettings();
        if ($this->shouldRegisterWebhooks) {
            return $this->stubBasecampProject($result->costlockerChangelog->projectId, $update);
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
        $p->costlockerProject->costlockerCompany = $this->company;
        return $p;
    }

    public function findCompanyByWebhook($webhookUrl)
    {
        return $this->company;
    }
}
