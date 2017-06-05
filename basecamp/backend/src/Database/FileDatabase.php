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

    public function findProject($costockerProjectId, $basecampProjectId = null)
    {
        $basecampProjects = $this->database[$costockerProjectId] ?? [];
        return $basecampProjects[$basecampProjectId] ?? reset($basecampProjects);
    }

    public function upsertProject($costockerProjectId, array $mapping)
    {
        $this->database[$costockerProjectId][$mapping['id']] = $mapping;
        file_put_contents($this->file, json_encode($this->database, JSON_PRETTY_PRINT));
    }
}
