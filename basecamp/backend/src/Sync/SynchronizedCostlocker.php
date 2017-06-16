<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Events\EventsLogger;
use Costlocker\Integrations\Entities\BasecampProject;

class SynchronizedCostlocker
{
    private $client;
    private $getUser;
    private $logger;
    private $webhookUrl;

    private $projectRequest;
    private $activities;

    public function __construct(CostlockerClient $c, GetUser $u, EventsLogger $l, $webhookUrl)
    {
        $this->client = $c;
        $this->getUser = $u;
        $this->logger = $l;
        $this->webhookUrl = $webhookUrl;
    }

    public function init(SyncProjectRequest $r)
    {
        $this->getUser->overrideCostlockerUser($r->costlockerUser);
        $this->projectRequest = $r;
        $this->activities = null;
    }

    public function loadProjectFromCostlocker($costlockerId)
    {
        $response = $this->client->__invoke("/projects/{$costlockerId}?types=peoplecosts");
        $project = json_decode($response->getBody(), true)['data'];

        $this->projectRequest->costlockerId = $project['id'];
        $this->projectRequest->projectItems = $project['items'];
        return $project;
    }

    public function updateProject(array $updatedItems)
    {
        $response = $this->client->__invoke("/projects", [
            'id' => $this->projectRequest->costlockerId,
            'items' => $updatedItems,
        ]);
        if ($response->getStatusCode() != 200) {
            return [true, $response->getBody()];
        }
        return [false, json_decode($response->getBody(), true)['data'][0]['items']];
    }

    public function findExistingActivity($activityName)
    {
        $normalizeName = function ($name) {
            return mb_strtolower($name, 'utf-8');
        };

        if ($this->activities === null) {
            $allActivities = json_decode(
                $this->client->__invoke('/v1/Simple_Activities')->getBody(),
                true
            );
            foreach ($allActivities as $activity) {
                if (!$activity['deactivated']) {
                    $this->activities[$normalizeName($activity['name'])] = $activity['id'];
                }
            }
        }

        return $this->activities[$normalizeName($activityName)] ?? null;
    }

    public function registerWebhook(BasecampProject $project)
    {
        $company = $project->costlockerProject->costlockerCompany;
        if ($company->urlWebhook && $this->existsWebhook($company->urlWebhook)) {
            return;
        }

        $response = $this->client->__invoke(
            '/webhooks',
            [
                'url' => $this->webhookUrl,
                'events' => [
                    'projects.create',
                    'peoplecosts.change',
                ],
            ]
        );

        if ($response->getStatusCode() != 200) {
            return;
        }

        $json = json_decode($response->getBody(), true);
        $company->urlWebhook = $json['data'][0]['links']['webhook'];
        $this->logger->__invoke(
            Event::REGISTER_COSTLOCKER_WEBHOOK,
            ['webhook' => $company->urlWebhook, 'company' => $company->id]
        );
    }

    private function existsWebhook($webhookUrl)
    {
        return $this->client->__invoke($webhookUrl)->getStatusCode() == 200;
    }
}
