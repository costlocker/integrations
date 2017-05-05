<?php

namespace Costlocker\Integrations;

use Costlocker\Integrations\Auth\GetUser;

class ImportDatabase
{
    private $user;
    private $database;
    private $encodingOptions;

    private $currentFile;
    private $currentCompany = [];

    public function __construct(GetUser $u, $dir, $encodingOptions = null)
    {
        $this->user = $u;
        $this->database = $dir;
        $this->encodingOptions = $encodingOptions;
    }

    public function saveProject(array $projectRequest, array $costlockerProject, array $costlockerTimeentries)
    {
        $this->loadDatabase();
        $harvestProjectId = $projectRequest['harvest'];
        $currentProject = $this->currentCompany['projects'][$harvestProjectId] ?? ['id' => $costlockerProject['id']];
        $this->currentCompany['projects'][$harvestProjectId] = $this->updateItemsMapping(
            $currentProject,
            $projectRequest['items'],
            $costlockerProject['items'],
            $costlockerTimeentries
        );
        $this->persist();
    }

    private function updateItemsMapping(array $mapping, array $requestItems, array $responseItems, array $timeentries)
    {
        $mapping += [
            'expenses' => [],
            'billing' => [],
            'activities' => [],
            'persons' => [],
            'timeentries' => [],
        ];
        foreach ($responseItems as $index => $item) {
            $harvestMapping = $requestItems[$index]['harvest'];
            switch ($item['item']['type']) {
                case 'expense':
                    $mapping['expenses'][$harvestMapping] = $item['item']['expense_id'];
                    break;
                case 'billing':
                    $mapping['billing'][$harvestMapping] = $item['item']['billing_id'];
                    break;
                case 'person':
                    $mapping['activities'][$harvestMapping['task']] = $item['item']['activity_id'];
                    $mapping['persons'][$harvestMapping['user']] = $item['item']['person_id'];
                    $timeentry = array_shift($timeentries);
                    if (isset($timeentry['uuid'])) {
                        $mapping['timeentries'][$harvestMapping['timeentry']] = $timeentry['uuid'];
                    }
                    break;
            }
        }
        return $mapping;
    }

    public function getBilling($projectId, $status)
    {
        return $this->getMapping($projectId, ['billing' => ['billing_id', $status]]);
    }

    public function getExpense($projectId, $expenseId)
    {
        return $this->getMapping($projectId, ['expenses' => ['expense_id', $expenseId]]);
    }

    public function getPerson($projectId, $taskId, $userId)
    {
        return $this->getMapping($projectId, [
            'activities' => ['activity_id', $taskId],
            'persons' => ['person_id', $userId],
        ]);
    }

    public function getTimeentry($projectId, $key)
    {
        return $this->getMapping($projectId, ['timeentries' => ['uuid', $key]]);
    }

    private function getMapping($projectId, array $fields)
    {
        $this->loadDatabase();
        $result = [];
        foreach ($fields as $group => list($field, $id)) {
            $mapping = $this->currentCompany['projects'][$projectId][$group] ?? [];
            if (array_key_exists($id, $mapping)) {
                $result += [$field => $mapping[$id]];
            }
        }
        return $result;
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
        file_put_contents($this->currentFile, json_encode($this->currentCompany, $this->encodingOptions));
    }
}
