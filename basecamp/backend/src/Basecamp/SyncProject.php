<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProject
{
    private $costlocker;
    private $basecampFactory;

    public function __construct(CostlockerClient $c, BasecampFactory $b)
    {
        $this->costlocker = $c;
        $this->basecampFactory = $b;
    }

    public function __invoke(array $config)
    {
        $response = $this->costlocker->__invoke("/projects/{$config['costlockerProject']}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];

        $basecamp = $this->basecampFactory->__invoke($config['account']);
        $bcProjectId = $basecamp->createProject(
            "{$project['client']['name']} | {$project['name']}",
            null,
            null
        );

        return [
            'costlocker' => $project,
            'basecamp' => [
                'id' => $bcProjectId,
            ],
        ];
    }
}
