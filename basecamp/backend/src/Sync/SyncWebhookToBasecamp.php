<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Basecamp\BasecampFactory;
use Costlocker\Integrations\Database\CompaniesRepository;

class SyncWebhookToBasecamp
{
    private $repository;
    private $database;
    private $synchronizer;

    public function __construct(CompaniesRepository $r, BasecampFactory $b, SyncDatabase $db)
    {
        $this->repository = $r;
        $this->database = $db;
        $this->synchronizer = new Synchronizer($b, $db);
    }

    public function __invoke(array $webhook)
    {
        $projects = $this->jsonEventsToProject($webhook['body']);
        $results = [];
        foreach ($projects as $id => $items) {
            $config = $this->getProjectSettings($id);

            $r = new SyncProjectRequest();
            $r->costlockerId = $id;
            $r->projectItems = $items;
            $r->isCompleteProjectSynchronized = false;
            $r->createProject = function () {
                return null; // creating new project in webhook is not supported
            };
            $results[] = $this->synchronizer->__invoke($r, $config);
        }
        return $results;
    }

    private function jsonEventsToProject(array $json )
    {
        $projects = [];

        $webhookUrl = $json['links']['webhook']['webhook'] ?? '';
        $company = $this->repository->findCompanyByWebhook($webhookUrl);

        if (!$company) {
            return $projects;
        }
        
        foreach ($json['data'] as $event) {
            if ($event['event'] != 'peoplecosts.change') {
                continue;
            }
            foreach ($event['data'] as $projectUpdate) {
                $id = $projectUpdate['id'];
                $projects[$id] = array_merge(
                    $projects[$id] ?? [],
                    $projectUpdate['items']
                );
            }
        }
        return $projects;
    }

    private function getProjectSettings($costlockerId)
    {
        $project = $this->database->findProject($costlockerId);

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
