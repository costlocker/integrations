<?php

namespace Costlocker\Integrations\Sync;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping;
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

    public function findProjects($costlockerProjectId)
    {
        return $this->mapping[$costlockerProjectId] ?? [];
    }
}
