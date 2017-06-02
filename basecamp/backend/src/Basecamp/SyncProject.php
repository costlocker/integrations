<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProject
{
    private $costlocker;

    public function __construct(CostlockerClient $c)
    {
        $this->costlocker = $c;
    }

    public function __invoke(array $config)
    {
        $response = $this->costlocker->__invoke("/projects/{$config['costlocker']}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];

        return [
            'costlocker' => $project,
        ];
    }
}
