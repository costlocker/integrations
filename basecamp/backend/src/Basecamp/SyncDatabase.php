<?php

namespace Costlocker\Integrations\Basecamp;

interface SyncDatabase
{
    public function findProject($costockerProjectId, $basecampProjectId = null);

    public function upsertProject($costockerProjectId, array $mapping);
}
