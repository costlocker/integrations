<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Basecamp\SyncDatabase;

class FileDatabase implements SyncDatabase
{
    private $file;
    private $database;

    public function __construct($file)
    {
        $this->file = $file;
        $this->database = is_file($file) ? json_decode(file_get_contents($file), true) : [];
    }

    public function findProject($costockerProjectId)
    {
        $basecampProjects = $this->findProjects($costockerProjectId);
        return reset($basecampProjects);
    }

    public function upsertProject($costockerProjectId, array $mapping)
    {
        $this->database[$costockerProjectId][$mapping['id']] = $mapping;
        $this->saveDb();
    }

    public function findProjects($costlockerProjectId)
    {
        // fixme: filter by tenant
        return $this->database[$costlockerProjectId] ?? [];
    }

    public function deleteProject($costlockerProjectId, $basecampProjectId)
    {
        unset($this->database[$costlockerProjectId][$basecampProjectId]);
        $this->saveDb();
    }

    private function saveDb()
    {
        file_put_contents($this->file, json_encode($this->database, JSON_PRETTY_PRINT));
    }
}
