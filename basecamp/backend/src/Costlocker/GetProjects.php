<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Basecamp\SyncDatabase;

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
        $response = $this->client->__invoke('/projects');
        $projects = [];
        foreach (json_decode($response->getBody(), true)['data'] as $rawProject) {
            $projects[] = [
                'id' => $rawProject['id'],
                'name' => $rawProject['name'],
                'client' => $rawProject['client'],
                'basecamps' => array_values(array_map(
                    function (array $mapping) {
                        return [
                            'id' => $mapping['id'],
                            'account' => $mapping['account'],
                            'url' => $this->buildProjectUrl($mapping['account'], $mapping['id']),
                            'settings' => $mapping['settings'],
                        ];
                    },
                    // fixme: fetch in loop
                    $this->database->findProjects($rawProject['id'])
                )),
            ];
        }
        usort($projects, function (array $a, array $b) {
            return count($b['basecamps']) - count($a['basecamps']);
        });
        return $projects;
    }

    private function buildProjectUrl(array $account, $projectId)
    {
        $basecamp = $this->basecampFactory->__invoke($account['id']);
        return $basecamp->buildProjectUrl(
            (object) [
                'bc__product_type' => $account['product'],
                'bc__account_href' => $account['href'],
                'bc__account_id' => $account['id'],
            ],
            $projectId
        );
    }
}
