<?php

namespace Costlocker\Integrations\Basecamp;

class InMemoryDatabase implements SyncDatabase
{
    private $mapping;

    public function findProject($costockerProjectId)
    {
        return $this->mapping[$costockerProjectId] ?? null;
    }

    public function upsertProject($costockerProjectId, array $mapping)
    {
        $this->mapping[$costockerProjectId] = $mapping;
    }
}
