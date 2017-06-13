<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Database\CompaniesRepository;
use Costlocker\Integrations\Entities\CostlockerCompany;

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
        $createdProjects = [];

        $webhookUrl = $json['links']['webhook']['webhook'] ?? '';
        $company = $this->repository->findCompanyByWebhook($webhookUrl);

        if (!$company) {
            return [];
        }
        
        foreach ($json['data'] as $event) {
            if ($event['event'] == 'peoplecosts.change') {
                foreach ($event['data'] as $projectUpdate) {
                    $id = $projectUpdate['id'];
                    $updatedProjects[$id] = array_merge(
                        $updatedProjects[$id] ?? [],
                        $projectUpdate['items']
                    );
                }
            } elseif ($event['event'] == 'projects.create') {
                foreach ($event['data'] as $createdProject) {
                    $createdProjects[] = $createdProject['id'];
                }
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

        if ($company->isCreatingBasecampProjectEnabled()) {
            foreach ($createdProjects as $id) {
                $config = $this->getCompanySettings($id, $company);

                $r = new SyncProjectRequest();
                $r->isCompleteProjectSynchronized = true;
                $results[] = [$r, $config];
            }
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

    private function getCompanySettings($costlockerId, CostlockerCompany $company)
    {
        $settings = $company->getSettings();

        $config = new SyncRequest();
        $config->account = $settings['account'];
        $config->costlockerProject = $costlockerId;
        $config->areTodosEnabled = $settings['areTodosEnabled'];
        if ($config->areTodosEnabled) {
            $config->isDeletingTodosEnabled = $settings['isDeletingTodosEnabled'];
            $config->isRevokeAccessEnabled = $settings['isRevokeAccessEnabled'];
        }

        return $config;
    }
}
