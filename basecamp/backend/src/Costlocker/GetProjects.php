<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Entities\BasecampProject;
use Costlocker\Integrations\Sync\SyncDatabase;

class GetProjects
{
    private $client;
    private $basecampFactory;
    private $database;

    public function __construct(CostlockerClient $c, BasecampFactory $b, SyncDatabase $db)
    {
        $this->client = $c;
        $this->basecampFactory = $b;
        $this->database = $db;
    }

    public function __invoke()
    {
        $response = $this->client->__invoke('/projects?state=running');
        $projects = [];
        foreach (json_decode($response->getBody(), true)['data'] as $rawProject) {
            $basecamps = [];
            // fixme: fetch in loop
            $project = $this->database->findBasecampProject($rawProject['id']);
            if ($project) {
                $basecamps[] = [
                    'id' => $project->id,
                    'settings' => $project->settings,
                    'account' => [
                        'id' => $project->basecampUser->id,
                        'basecampId' => $project->basecampUser->basecampAccount->id,
                        'name' => $project->basecampUser->basecampAccount->name,
                        'product' => $project->basecampUser->basecampAccount->product,
                        'href' => $project->basecampUser->basecampAccount->urlApi,
                        'identity' => $project->basecampUser->data,
                    ],
                    'url' => $this->basecampFactory->buildProjectUrl($project),
                ];
            }
            $projects[] = [
                'id' => $rawProject['id'],
                'name' => $rawProject['name'],
                'client' => $rawProject['client'],
                'basecamps' => $basecamps,
            ];
        }
        usort($projects, function (array $a, array $b) {
            return count($b['basecamps']) - count($a['basecamps']);
        });
        return $projects;
    }
}
