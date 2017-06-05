<?php

namespace Costlocker\Integrations\Basecamp;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping;

    public function findProject($costockerProjectId, $basecampProjectId = null)
    {
        $basecampProjects = $this->mapping[$costockerProjectId] ?? [];
        return $basecampProjects[$basecampProjectId] ?? reset($basecampProjects);
    }

    public function upsertProject($costockerProjectId, array $mapping)
    {
        $this->mapping[$costockerProjectId][$mapping['id']] = $mapping;
    }

    public function findProjects($costlockerProjectId)
    {
        return $this->database[$costlockerProjectId] ?? [];
    }
}
