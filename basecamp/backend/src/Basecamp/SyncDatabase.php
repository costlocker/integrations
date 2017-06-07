<?php

namespace Costlocker\Integrations\Basecamp;

interface SyncDatabase
{
    public function findProjects($costlockerProjectId);

    public function findProject($costockerProjectId);

    public function upsertProject($costockerProjectId, array $mapping);

    public function deleteProject($costlockerProjectId, $basecampProjectId);
}
