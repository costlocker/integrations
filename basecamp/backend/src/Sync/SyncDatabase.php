<?php

namespace Costlocker\Integrations\Sync;

interface SyncDatabase
{
    public function findProjects($costlockerProjectId);

    public function findProject($costockerProjectId);

    public function findBasecampProject($costockerProjectId);

    public function findBasecampProjectById($basecampProjectId);

    public function upsertProject($costockerProjectId, array $mapping, array $settings = []);
}
