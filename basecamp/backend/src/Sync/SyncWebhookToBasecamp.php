<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Database\CompaniesRepository;

class SyncWebhookToBasecamp
{
    private $repository;
    private $synchronizer;

    public function __construct(CompaniesRepository $r, Synchronizer $s)
    {
        $this->repository = $r;
        $this->synchronizer = $s;
    }

    public function __invoke(array $webhook)
    {
        $requests = $this->jsonEventsToRequests($webhook['body']);
        $results = [];
        foreach ($requests as list($r, $config)) {
            $results[] = $this->synchronizer->__invoke($r, $config);
        }
        return $results;
    }

    private function jsonEventsToRequests(array $json )
    {
        $updatedProjects = [];

        $webhookUrl = $json['links']['webhook']['webhook'] ?? '';
        $company = $this->repository->findCompanyByWebhook($webhookUrl);

        if (!$company) {
            return [];
        }
        
        foreach ($json['data'] as $event) {
            if ($event['event'] != 'peoplecosts.change') {
                continue;
            }
            foreach ($event['data'] as $projectUpdate) {
                $id = $projectUpdate['id'];
                $updatedProjects[$id] = array_merge(
                    $updatedProjects[$id] ?? [],
                    $projectUpdate['items']
                );
            }
        }

        $results = [];
        foreach ($updatedProjects as $id => $items) {
            $config = $this->getProjectSettings($id);

            $r = new SyncProjectRequest();
            $r->costlockerId = $id;
            $r->projectItems = $items;
            $r->isCompleteProjectSynchronized = false;
            $r->createProject = function () {
                return null; // creating new project in webhook is not supported
            };
            $results[] = [$r, $config];
        }
        return $results;
    }

    private function getProjectSettings($costlockerId)
    {
        $project = $this->synchronizer->findProject($costlockerId);

        $config = new SyncRequest();
        $config->costlockerProject = $costlockerId;
        $config->isRevokeAccessEnabled = false; // always override (not all people are loaded)

        if ($project) {
            $config->account = $project['account']['id'] ?? $config->account;
            $options = ['areTodosEnabled', 'isDeletingTodosEnabled'];
            foreach ($options as $option) {
                if (array_key_exists($option, $project['settings'])) {
                    $config->{$option} = $project['settings'][$option];
                }
            }
        }

        return $config;
    }
}
