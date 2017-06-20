<?php

namespace Costlocker\Integrations\Events;

use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Sync\SyncChangelog;

class EventsToJson
{
    public function __invoke(array $events)
    {
        return array_map(
            function (Event $e) {
                $isRequest = $e->event == Event::SYNC_REQUEST;
                $date = $isRequest ? $e->createdAt : ($e->updatedAt ?: $e->createdAt);
                return [
                    'id' => $e->id,
                    'description' => $this->eventToDescription($e),
                    'date' => $date->format('Y-m-d H:i:s'),
                    'user' => $e->costlockerUser ? $e->costlockerUser->data : null,
                    'status' => $this->eventToStatus($e),
                    'errors' => array_values(array_filter([
                        $e->data['result']['basecamp']['error'] ?? null,
                        $e->data['result']['costlocker']['error'] ?? null,
                    ])),
                    'changelogs' => array_values(array_filter([
                        SyncChangelog::arrayToStats('basecamp', $e->data['result']['basecamp'] ?? []),
                        SyncChangelog::arrayToStats('costlocker', $e->data['result']['costlocker'] ?? []),
                    ])),
                    'project' => [
                        'costlocker' => $e->basecampProject ? $e->basecampProject->costlockerProject->id : null,
                    ],
                ];
            },
            $events
        );
    }

    private function eventToDescription(Event $e)
    {
        $mapping = [
            Event::WEBHOOK_SYNC => 'webhook sync',
            Event::MANUAL_SYNC => 'manual sync',
            Event::WEBHOOK_SYNC | Event::RESULT_SUCCESS => 'Successful sync after webhook',
            Event::WEBHOOK_SYNC | Event::RESULT_FAILURE => 'Failed sync after webhook',
            Event::WEBHOOK_SYNC | Event::RESULT_NOCHANGE => 'No change after webhook sync',
            Event::WEBHOOK_SYNC | Event::RESULT_PARTIAL_SUCCESS => 'partial sync after webhook sync',
            Event::MANUAL_SYNC | Event::RESULT_SUCCESS => 'Successful sync after user request',
            Event::MANUAL_SYNC | Event::RESULT_FAILURE => 'Failed sync after user request',
            Event::MANUAL_SYNC | Event::RESULT_NOCHANGE => 'No change after user request sync',
            Event::MANUAL_SYNC | Event::RESULT_PARTIAL_SUCCESS => 'partial sync after user request',
            Event::WEBHOOK_BASECAMP => 'Basecamp webhook',
            Event::WEBHOOK_BASECAMP | Event::RESULT_SUCCESS => 'Successful sync after basecamp webhooks',
            Event::WEBHOOK_BASECAMP | Event::RESULT_FAILURE => 'Failed sync after basecamp webhooks',
            Event::WEBHOOK_BASECAMP | Event::RESULT_NOCHANGE => 'No change after basecamp webhooks',
            Event::WEBHOOK_BASECAMP | Event::RESULT_PARTIAL_SUCCESS => 'partial sync after basecamp webhooks',
            // disconnect should be successful 99%, so using results is not necessary
            Event::DISCONNECT_BASECAMP => 'Disconnect basecamp account',
            Event::DISCONNECT_PROJECT => 'Disconnect project',
            Event::REGISTER_COSTLOCKER_WEBHOOK => 'Register costlocker webhook',
            Event::REGISTER_BASECAMP_WEBHOOK => 'Update basecamp webhook for project',
        ];

        $description = $mapping[$e->event] ?? '';
        if ($e->event == Event::SYNC_REQUEST) {
            $description = "Request {$mapping[$e->data['type']]}";
        } elseif ($e->event == Event::DISCONNECT_BASECAMP) {
            $description .= " {$e->data['basecamp']}";
        } elseif ($e->event == Event::DISCONNECT_PROJECT) {
            $description .= " #{$e->data['project']}";
        } elseif ($e->event == Event::WEBHOOK_BASECAMP) {
            $description .=
                " '{$e->data['basecampEvent']}' " .
                "in project #{$e->basecampProject->costlockerProject->id}";
        } elseif ($e->basecampProject) {
            $description .= " #{$e->basecampProject->costlockerProject->id}";
        }
        return $description;
    }

    private function eventToStatus(Event $e)
    {
        $statuses = [
            ($e->event | Event::RESULT_SUCCESS) => 'success',
            ($e->event | Event::RESULT_FAILURE) => 'failure',
            ($e->event | Event::RESULT_NOCHANGE) => 'nochange',
            ($e->event | Event::RESULT_PARTIAL_SUCCESS) => 'partial',
        ];
        return $statuses[$e->event] ?? null;
    }
}
