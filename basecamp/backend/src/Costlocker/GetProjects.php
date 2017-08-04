<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampAdapter;
use Costlocker\Integrations\Entities\BasecampProject;
use Costlocker\Integrations\Database\ProjectsDatabase;

class GetProjects
{
    private $client;
    private $basecamps;
    private $database;

    public function __construct(CostlockerClient $c, BasecampAdapter $b, ProjectsDatabase $db)
    {
        $this->client = $c;
        $this->basecamps = $b;
        $this->database = $db;
    }

    public function __invoke()
    {
        $mappedProjects = $this->database->findAll();
        $response = $this->client->__invoke('/projects?state=running');
        $projects = [];
        foreach (json_decode($response->getBody(), true)['data'] as $rawProject) {
            $basecamps = [];
            $basecampProject = $mappedProjects[$rawProject['id']] ?? null;
            if ($basecampProject instanceof BasecampProject) {
                $basecamps[] = [
                    'id' => $basecampProject->id,
                    'settings' => $basecampProject->getSettings(),
                    'account' => [
                        'id' => $basecampProject->basecampUser->id,
                        'basecampId' => $basecampProject->basecampUser->basecampAccount->id,
                        'name' => $basecampProject->basecampUser->basecampAccount->name,
                        'product' => $basecampProject->basecampUser->basecampAccount->product,
                        'href' => $basecampProject->basecampUser->basecampAccount->urlApi,
                        'identity' => $basecampProject->basecampUser->data,
                    ],
                    'url' => $this->basecamps->buildBasecampLink($basecampProject),
                ];
            }
            $projects[] = [
                'id' => $rawProject['id'],
                'name' => $rawProject['name'],
                'client' => $rawProject['client'],
                'url' => getenv('CL_HOST') . "/projects/detail/{$rawProject['id']}/cost-estimate",
                'basecamps' => $basecamps,
                'basecamp' => reset($basecamps) ?: null,
            ];
        }
        return $this->sortProjects($projects);
    }

    private function sortProjects(array $projects)
    {
        usort($projects, function (array $a, array $b) {
            $basecampsDiff = count($b['basecamps']) - count($a['basecamps']);
            if ($basecampsDiff) {
                return $basecampsDiff;
            }
            return strcasecmp($a['name'], $b['name']);
        });
        return $projects;
    }
}
