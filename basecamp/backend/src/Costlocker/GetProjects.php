<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;

class GetProjects
{
    private $client;

    public function __construct(CostlockerClient $c)
    {
        $this->client = $c;
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
            ];
        }
        return $projects;
    }
}
