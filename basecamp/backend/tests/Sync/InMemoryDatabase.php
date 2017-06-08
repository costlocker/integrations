<?php

namespace Costlocker\Integrations\Sync;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping;

    public function findProject($costockerProjectId)
    {
        $basecampProjects = $this->mapping[$costockerProjectId] ?? [];
        return reset($basecampProjects);
    }

    public function upsertProject($costockerProjectId, array $mapping, array $settings = [])
    {
        $this->mapping[$costockerProjectId][$mapping['id']] = $mapping;
    }

    public function findProjects($costlockerProjectId)
    {
        return $this->mapping[$costlockerProjectId] ?? [];
    }

    public function deleteProject($costlockerProjectId, $basecampProjectId)
    {
        unset($this->mapping[$costlockerProjectId][$basecampProjectId]);
    }
}
