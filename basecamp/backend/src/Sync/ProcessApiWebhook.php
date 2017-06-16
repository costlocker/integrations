<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\Event;

class ProcessApiWebhook
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
        } else {
            return $this->processCostlockerWebhooks($webhook['body']);
        }
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

    private function processCostlockerWebhooks(array $json)
    {
        $requests = $this->processCostlockerWebhook($json);
        $results = [];
        foreach ($requests as $r) {
            $results[] = $this->synchronizer->__invoke($r);
        }
        return $results;
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

        $requests = [];
        foreach ($updatedProjects as $id => $items) {
            $r = new SyncRequest();
            $r->costlockerId = $id;
            $r->projectItems = $items;
            $r->isCompleteProjectSynchronized = false;
            $this->loadUpdatedProjectSettings($r);
            $requests[] = $r;
        }

        if ($company->isCreatingBasecampProjectEnabled()) {
            foreach ($createdProjects as $id) {
                $r = SyncRequest::completeSynchronization($company->defaultCostlockerUser);
                $r->costlockerId = $id;
                $this->loadCreatedProjectSettings($r, $company);
                $requests[] = $r;
            }
        }

        return $requests;
    }

    private function loadUpdatedProjectSettings(SyncRequest $request)
    {
        $project = $this->database->findByCostlockerId($request->costlockerId);

        if ($project instanceof \Costlocker\Integrations\Entities\BasecampProject) {
            $request->account = $project->basecampUser->id;
            $request->settings->loadCostlockerSettings($project->settings);
        }
        $request->settings->disableUpdatingCostlocker();
    }

    private function loadCreatedProjectSettings(SyncRequest $request, CostlockerCompany $company)
    {
        $settings = $company->getSettings();
        $request->account = $settings['account'];
        $request->settings->loadCostlockerSettings($settings);
        $request->settings->loadBasecampSettings($settings);
    }
}
