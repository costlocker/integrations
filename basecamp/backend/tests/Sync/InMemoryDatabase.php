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
        $this->lastSettings = $settings;
    }

    public function findBasecampProject($costockerProjectId)
    {
        return null;
    }

    public function findBasecampProjectById($basecampProjectId)
    {
        foreach ($this->mapping as $projects) {
            foreach (array_keys($projects) as $basecampId) {
                if ($basecampId == $basecampProjectId) {
                    return new BasecampProject();
                }
            }
        }
    }

    public function findProjects($costlockerProjectId)
    {
        return $this->mapping[$costlockerProjectId] ?? [];
    }
}
