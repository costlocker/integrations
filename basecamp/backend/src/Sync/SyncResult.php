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

    public $costlockerChangelog;
    public $basecampChangelog;

    public function __construct(SyncProjectRequest $r, SyncRequest $c)
    {
        $this->projectRequest = $r;
        $this->syncConfig = $c;
        $this->costlockerChangelog = new SyncChangelog();
        $this->basecampChangelog = new SyncChangelog();
    }

    public function getResultStatus()
    {
        if (
            $this->costlockerChangelog->error ||
            $this->basecampChangelog->error
        ) {
            return Event::RESULT_FAILURE;
        } elseif (
            $this->costlockerChangelog->wasSomethingChanged() ||
            $this->basecampChangelog->wasSomethingChanged()
        ) {
            return Event::RESULT_SUCCESS;
        } else {
            return Event::RESULT_NOCHANGE;
        }
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
            'basecamp' => $this->basecampChangelog->toArray($this->basecampProjectId),
            'costlocker' => $this->basecampChangelog->toArray($this->projectRequest->costlockerId),
        ];
        // dont save doctrine entity...
        if ($this->projectRequest->costlockerUser) {
            $data['request']['project']['costlockerUser'] = $this->projectRequest->costlockerUser->id;
        }
        return $data;
    }
}
