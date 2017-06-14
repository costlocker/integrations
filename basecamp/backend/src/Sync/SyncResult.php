<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\Event;

class SyncResult
{
    private $projectRequest;
    /** @var SyncRequest */
    public $syncConfig;

    public $basecampProjectId;
    /** @var \Costlocker\Integrations\Entities\BasecampProject */
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
    }

    public function getResultStatus()
    {
        if ($this->error) {
            return Event::RESULT_FAILURE;
        } elseif ($this->wasProjectCreated || $this->todolists || $this->wasSomethingDeleted()) {
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

    public function getSettings()
    {
        return $this->syncConfig->toSettings();
    }

    public function toArray()
    {
        $data = [
            'request' => [
                'sync' => get_object_vars($this->syncConfig),
                'project' => get_object_vars($this->projectRequest),
                'settings' => $this->getSettings(),
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
        // dont save doctrine entity...
        if ($this->projectRequest->costlockerUser) {
            $data['request']['project']['costlockerUser'] = $this->projectRequest->costlockerUser->id;
        }
        return $data;
    }
}
