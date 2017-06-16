<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class SyncWebhookToBasecamp
{
    private $database;
    private $synchronizer;
    private $eventsLogger;

    public function __construct(SyncDatabase $db, Synchronizer $s, EventsLogger $e)
    {
        $this->database = $db;
        $this->synchronizer = $s;
        $this->eventsLogger = $e;
    }

    public function __invoke(array $webhook)
    {
        if ($this->isBasecampWebhook($webhook['headers'])) {
            return $this->processBasecampWebhook($webhook['body']);
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

        if (!in_array($json['kind'] ?? '', $allowedWebhooks)) {
            return "Not allowed basecamp event";
        }

        $basecampId = $json['recording']['bucket']['id'];
        $project = $this->database->findByBasecampId($basecampId);
        if (!$project || $project->isBasecampSynchronizationDisabled()) {
            return "Unmapped or disabled basecamp synchronization";
        }

        $this->eventsLogger->__invoke(
            Event::WEBHOOK_BASECAMP,
            [
                'costlockerProject' => $project->costlockerProject->id,
                'basecampProject' => $basecampId,
                'basecampEvent' => $json['kind'],
            ],
            $project
        );
    }

    private function processCostlockerWebhook(array $json)
    {
        $updatedProjects = [];
        $createdProjects = [];

        $webhookUrl = $json['links']['webhook']['webhook'] ?? '';
        $company = $this->database->findCompanyByWebhook($webhookUrl);

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
        $project = $this->database->findByCostlockerId($costlockerId);

        $config = new SyncRequest();
        $config->costlockerProject = $costlockerId;
        $config->isRevokeAccessEnabled = false; // always override (not all people are loaded)

        if ($project instanceof \Costlocker\Integrations\Entities\BasecampProject) {
            $config->account = $project->basecampUser->id;
            $options = ['areTodosEnabled', 'isDeletingTodosEnabled'];
            foreach ($options as $option) {
                if (array_key_exists($option, $project->settings)) {
                    $config->{$option} = $project->settings[$option];
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
