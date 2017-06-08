<?php

namespace Costlocker\Integrations\Basecamp;

use Costlocker\Integrations\Database\Event;

class SyncResult
{
    private $projectRequest;
    private $syncConfig;
    public $settings;

    public $basecampProjectId;
    /** @var \Costlocker\Integrations\Database\BasecampProject */
    public $mappedProject;
    public $error;

    public $wasProjectCreated = false;
    public $grantedPeople = [];
    public $todolists = [];
    public $deleteSummary = [];

    public function __construct(SyncProjectRequest $r, SyncRequest $c)
    {
        $this->projectRequest = $r;
        $this->syncConfig = $c;
        $this->settings = $c->toSettings();
    }

    public function getResultStatus()
    {
        if ($this->error) {
            return Event::RESULT_FAILURE;
        } elseif ($this->wasProjectCreated || $this->grantedPeople || $this->todolists || $this->wasSomethingDeleted()) {
            return Event::RESULT_SUCCESS;
        } else {
            return Event::RESULT_NOCHANGE;
        }
    }

    private function wasSomethingDeleted()
    {
        foreach ($this->deleteSummary as $deleted) {
            if ($deleted) {
                return true;
            }
        }
        return false;
    }

    public function toArray()
    {
        return [
            'request' => [
                'sync' => get_object_vars($this->syncConfig),
                'project' => get_object_vars($this->projectRequest),
                'settings' => $this->settings,
            ],
            'costlocker' => [
                'id' => $this->projectRequest->costlockerId,
                'items' => $this->projectRequest->projectItems,
            ],
            'basecamp' => [
                'id' => $this->basecampProjectId,
                'wasProjectCreated' => $this->wasProjectCreated,
                'people' => $this->grantedPeople,
                'activities' => $this->todolists,
                'delete' => $this->deleteSummary,
                'error' => $this->error,
            ],
        ];
    }
}
