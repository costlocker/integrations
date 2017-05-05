<?php

namespace Costlocker\Integrations;

class ImportDatabase
{
    private $database;
    private $currentFile;
    private $currentCompany = [];

    public function __construct($dir)
    {
        $this->database = $dir;
    }

    public function setHarvestAccount($harvestDomain)
    {
        $this->currentFile = "{$this->database}/{$harvestDomain}.json";
        if (file_exists($this->currentFile)) {
            $this->currentCompany = json_decode(file_get_contents($this->currentFile), true);
        } else {
            $this->currentCompany = [];
        }
    }

    public function saveProject(array $harvestProject, array $costlockerProject)
    {
        $this->currentCompany['projects'][$harvestProject['selectedProject']['id']] = $costlockerProject['id'];
        $this->persist();
    }

    public function getProjects()
    {
        return array_keys($this->currentCompany['projects'] ?? []);
    }

    private function persist()
    {
        file_put_contents($this->currentFile, json_encode($this->currentCompany));
    }
}
