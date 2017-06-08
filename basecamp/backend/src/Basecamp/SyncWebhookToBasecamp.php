<?php

namespace Costlocker\Integrations\Basecamp;

class SyncWebhookToBasecamp
{
    private $synchronizer;

    public function __construct(BasecampFactory $b, SyncDatabase $db)
    {
        $this->synchronizer = new Synchronizer($b, $db);
    }

    public function __invoke($jsonEvents)
    {
        $projects = $this->jsonEventsToProject($jsonEvents);
        $results = [];
        foreach ($projects as $id => $items) {
            $config = new SyncRequest();
            $config->areTodosEnabled = true;
            $config->isDeletingTodosEnabled = true;
            $config->isRevokeAccessEnabled = false; // always override because not all people are loaded

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

    private function jsonEventsToProject($jsonEvents)
    {
        $json = json_decode($jsonEvents, true);
        $projects = [];
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
}
