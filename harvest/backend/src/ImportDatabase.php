<?php

namespace Costlocker\Integrations;

use Costlocker\Integrations\Auth\GetUser;

class ImportDatabase
{
    private $user;
    private $database;
    private $currentFile;
    private $currentCompany = [];

    public function __construct(GetUser $u, $dir)
    {
        $this->user = $u;
        $this->database = $dir;
    }

    public function saveProject(array $harvestProject, array $costlockerProject)
    {
        $this->loadDatabase();
        $this->currentCompany['projects'][$harvestProject['selectedProject']['id']] = $costlockerProject['id'];
        $this->persist();
    }

    public function separateProjectsByStatus(array $projects)
    {
        $this->loadDatabase();
        $mappedProjects = array_keys($this->currentCompany['projects'] ?? []);
        $result = ['new' => [], 'imported' => []];
        foreach ($projects as $project) {
            $status = in_array($project['id'], $mappedProjects) ? 'imported' : 'new';
            $result[$status][] = $project;
        }
        return $result;
    }

    private function loadDatabase()
    {
        if ($this->currentFile) {
            return;
        }
        $this->currentFile = "{$this->database}/{$this->user->getHarvestSubdomain()}.json";
        if (file_exists($this->currentFile)) {
            $this->currentCompany = json_decode(file_get_contents($this->currentFile), true);
        } else {
            $this->currentCompany = [];
        }
    }

    private function persist()
    {
        file_put_contents($this->currentFile, json_encode($this->currentCompany));
    }
}
