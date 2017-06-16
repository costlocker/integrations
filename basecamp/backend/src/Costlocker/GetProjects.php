<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampAdapter;
use Costlocker\Integrations\Sync\SyncDatabase;

class GetProjects
{
    private $client;
    private $basecamps;
    private $database;

    public function __construct(CostlockerClient $c, BasecampAdapter $b, SyncDatabase $db)
    {
        $this->client = $c;
        $this->basecamps = $b;
        $this->database = $db;
    }

    public function __invoke()
    {
        $response = $this->client->__invoke('/projects?state=running');
        $projects = [];
        foreach (json_decode($response->getBody(), true)['data'] as $rawProject) {
            $basecamps = [];
            // fixme: fetch in loop
            $project = $this->database->findByCostlockerId($rawProject['id']);
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
                    'url' => $this->basecamps->buildBasecampLink($project),
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
