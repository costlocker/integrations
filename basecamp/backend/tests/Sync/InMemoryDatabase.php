<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\BasecampProject;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping = [];
    public $lastSettings;

    public function findProject($costockerProjectId)
    {
        $basecampProjects = $this->mapping[$costockerProjectId] ?? [];
        return reset($basecampProjects);
    }

    public function upsertProject($costockerProjectId, array $mapping, array $settings = [])
    {
        $this->mapping[$costockerProjectId][$mapping['id']] = $mapping;
        $this->lastSettings = $settings ?: $mapping['settings'];
    }

    public function findBasecampProject($costockerProjectId)
    {
        foreach ($this->mapping as $costlockerId => $mappings) {
            if ($costlockerId == $costockerProjectId) {
                return $this->stubBasecampProject($costlockerId, reset($mappings));
            }
        }
    }

    public function findBasecampProjectById($basecampProjectId)
    {
        foreach ($this->mapping as $costlockerId => $projects) {
            foreach ($projects as $basecampId => $mapping) {
                if ($basecampId == $basecampProjectId) {
                    return $this->stubBasecampProject($costlockerId, $mapping);
                }
            }
        }
    }

    private function stubBasecampProject($costlockerId, array $mapping)
    {
        $p = new BasecampProject();
        $p->mapping = $mapping;
        $p->settings = $this->lastSettings;
        $p->basecampUser = new \Costlocker\Integrations\Entities\BasecampUser();
        $p->costlockerProject = new \Costlocker\Integrations\Entities\CostlockerProject();
        $p->costlockerProject->id = $costlockerId;
        $p->costlockerProject->costlockerCompany = new \Costlocker\Integrations\Entities\CostlockerCompany();
        return $p;
    }

    public function findProjects($costlockerProjectId)
    {
        return $this->mapping[$costlockerProjectId] ?? [];
    }
}
