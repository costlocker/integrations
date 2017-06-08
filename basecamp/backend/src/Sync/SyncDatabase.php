<?php

namespace Costlocker\Integrations\Sync;

interface SyncDatabase
{
    public function findProjects($costlockerProjectId);

    public function findProject($costockerProjectId);

    public function upsertProject($costockerProjectId, array $mapping, array $settings = []);

    public function deleteProject($costlockerProjectId, $basecampProjectId);
}
