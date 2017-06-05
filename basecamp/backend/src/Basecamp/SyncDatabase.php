<?php

namespace Costlocker\Integrations\Basecamp;

interface SyncDatabase
{
    public function findProject($costockerProjectId);

    public function upsertProject($costockerProjectId, array $mapping);
}
