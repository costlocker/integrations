<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\CostlockerClient;

class SyncProject
{
    private $basecampFactory;

    private $costlocker;
    /** @var \Costlocker\Integrations\Basecamp\Api\BasecampApi */
    private $basecamp;

    public function __construct(CostlockerClient $c, BasecampFactory $b)
    {
        $this->costlocker = $c;
        $this->basecampFactory = $b;
    }

    public function __invoke(array $config)
    {
        $response = $this->costlocker->__invoke("/projects/{$config['costlockerProject']}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];
        $peopleFromCostlocker = $this->analyzeProjectItems($project['items']);

        $this->basecamp = $this->basecampFactory->__invoke($config['account']);
        $bcProjectId = $this->createProject($project);
        $grantedPeople = $this->grantAccess($bcProjectId, $peopleFromCostlocker);

        return [
            'costlocker' => $project,
            'basecamp' => [
                'id' => $bcProjectId,
                'people' => $grantedPeople,
            ],
        ];
    }

    private function analyzeProjectItems(array $projectItems)
    {
        $persons = [];
        foreach ($projectItems as $item) {
            if ($item['item']['type'] == 'person') {
                $person = $item['person'];
                $persons[$person['email']] = "{$person['first_name']} {$person['last_name']}";
            }
        }
        return $persons;
    }

    private function createProject(array $project)
    {
        $name = "{$project['client']['name']} | {$project['name']}";
        return $this->basecamp->createProject($name, null, null);
    }

    private function grantAccess($bcProjectId, array $peopleFromCostlocker)
    {
        $peopleEmails = array();
        foreach ($peopleFromCostlocker as $email => $fullname) {
            $peopleEmails["{$fullname} ({$email})"] = $email;
        }
        $this->basecamp->grantAccess($bcProjectId, $peopleEmails);
        return $peopleEmails;
    }
}
