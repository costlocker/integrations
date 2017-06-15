<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Database\CompaniesRepository;
use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class SyncWebhookToBasecamp
{
    private $repository;
    private $synchronizer;
    private $eventsLogger;

    public function __construct(CompaniesRepository $r, Synchronizer $s, EventsLogger $e)
    {
        $this->repository = $r;
        $this->synchronizer = $s;
        $this->eventsLogger = $e;
    }

    public function __invoke(array $webhook)
    {
        if ($this->isBasecampWebhook($webhook['headers'])) {
            $this->processBasecampWebhook($webhook['body']);
            return [];
        }

        $requests = $this->processCostlockerWebhook($webhook['body']);
        $results = [];
        foreach ($requests as list($r, $config)) {
            $results[] = $this->synchronizer->__invoke($r, $config);
        }
        return $results;
    }

    private function isBasecampWebhook(array $headers)
    {
        return in_array('Basecamp3 Webhook', (array) ($headers['user-agent'] ?? []));
    }

    private function processBasecampWebhook(array $json)
    {
        $allowedWebhooks = [
            'todo_archived', 'todo_assignment_changed', 'todo_content_changed', 'todo_created',
            'todo_trashed', 'todo_unarchived', 'todo_untrashed',
            'todolist_archived', 'todolist_created', 'todolist_name_changed', 
            'todolist_trashed', 'todolist_unarchived', 'todolist_untrashed',
        ];
        $webhook = [
            'event' => $json['kind'],
            'project' => $json['recording']['bucket']['id'],
        ];

        if (!in_array($webhook['event'] ?? '', $allowedWebhooks)) {
            return;
        }

        $project = $this->synchronizer->findProjectByBasecampId($webhook['project']);
        if (!$project || $project->isBasecampSynchronizationDisabled()) {
            return;
        }

        $this->eventsLogger->__invoke(Event::WEBHOOK_BASECAMP, ['basecamp' => $webhook]);
    }

    private function processCostlockerWebhook(array $json)
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
            $results[] = [$r, $config];
        }

        if ($company->isCreatingBasecampProjectEnabled()) {
            foreach ($createdProjects as $id) {
                $config = $this->getCompanySettings($id, $company);

                $r = SyncProjectRequest::completeSynchronization($company->defaultCostlockerUser);
                $results[] = [$r, $config];
            }
        }

        return $results;
    }

    private function getProjectSettings($costlockerId)
    {
        $project = $this->synchronizer->findProjectByCostlockerId($costlockerId);

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
