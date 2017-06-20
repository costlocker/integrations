<?php

namespace Costlocker\Integrations\Sync;

use Costlocker\Integrations\Entities\Event;

class SyncResponse
{
    /** @var SyncRequest */
    public $request;
    /** @var \Costlocker\Integrations\Entities\BasecampProject */
    public $mappedProject;
    /** @var array */
    public $newMapping;

    /** @var SyncChangelog */
    public $costlockerChangelog;
    /** @var SyncChangelog */
    public $basecampChangelog;
    public $webhookError;

    public function __construct(SyncRequest $r)
    {
        $this->request = $r;
        $this->costlockerChangelog = new SyncChangelog();
        $this->basecampChangelog = new SyncChangelog();
    }

    public function getResultStatus()
    {
        $hasError = $this->costlockerChangelog->error || $this->basecampChangelog->error;
        $hasSucceed =
            $this->costlockerChangelog->wasSomethingChanged() ||
            $this->basecampChangelog->wasSomethingChanged();

        if ($hasSucceed && $hasError) {
            return Event::RESULT_PARTIAL_SUCCESS;
        } elseif ($hasError) {
            return Event::RESULT_FAILURE;
        } elseif ($hasSucceed) {
            return Event::RESULT_SUCCESS;
        } else {
            return Event::RESULT_NOCHANGE;
        }
    }

    public function getSettings()
    {
        return $this->request->settings->toArray();
    }

    public function toArray()
    {
        $data = [
            'request' => get_object_vars($this->request),
            'basecamp' => $this->basecampChangelog->toArray(),
            'costlocker' => $this->costlockerChangelog->toArray(),
            'webhooks' => [
                'error' => $this->webhookError,
            ],
        ];
        // dont save doctrine entity...
        if ($this->request->costlockerUser) {
            $data['request']['costlockerUser'] = $this->request->costlockerUser->id;
        }
        return $data;
    }
}
