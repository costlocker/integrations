<?php

namespace Costlocker\Integrations\Events;

use Costlocker\Integrations\Entities\Event;

class EventsToJson
{
    public function __invoke(array $events)
    {
        return array_map(
            function (Event $e) {
                $mapping = [
                    Event::WEBHOOK_SYNC => 'webhook sync',
                    Event::MANUAL_SYNC => 'manual sync',
                    Event::WEBHOOK_SYNC | Event::RESULT_SUCCESS => 'Successful sync after webhook',
                    Event::WEBHOOK_SYNC | Event::RESULT_FAILURE => 'Failed sync after webhook',
                    Event::WEBHOOK_SYNC | Event::RESULT_NOCHANGE => 'No change after webhook sync',
                    Event::MANUAL_SYNC | Event::RESULT_SUCCESS => 'Successful sync after user request',
                    Event::MANUAL_SYNC | Event::RESULT_FAILURE => 'Failed sync after user request',
                    Event::MANUAL_SYNC | Event::RESULT_NOCHANGE => 'No change after user request sync',
                    // disconnect should be successful 99%, so using results is not necessary
                    Event::DISCONNECT_BASECAMP => 'Disconnect basecamp account',
                    Event::DISCONNECT_PROJECT => 'Disconnect project',
                    Event::REGISTER_WEBHOOK => 'Register costlocker webhook',
                ];
                $isRequest = $e->event == Event::SYNC_REQUEST;
                $description = $mapping[$e->event] ?? '';
                if ($isRequest) {
                    $description = "Request {$mapping[$e->data['type']]}";
                } elseif ($e->event == Event::DISCONNECT_BASECAMP) {
                    $description .= " {$e->data['basecamp']}";
                } elseif ($e->event == Event::DISCONNECT_PROJECT) {
                    $description .= " #{$e->data['project']}";
                } elseif ($e->basecampProject) {
                    $description .= " #{$e->basecampProject->costlockerProject->id}";
                }
                $statuses = [
                    ($e->event | Event::RESULT_SUCCESS) => 'success',
                    ($e->event | Event::RESULT_FAILURE) => 'failure',
                    ($e->event | Event::RESULT_NOCHANGE) => 'nochange',
                ];
                $date = $isRequest ? $e->createdAt : ($e->updatedAt ?: $e->createdAt);
                return [
                    'id' => $e->id,
                    'description' => $description,
                    'date' => $date->format('Y-m-d H:i:s'),
                    'user' => $e->costlockerUser ? $e->costlockerUser->data : null,
                    'status' => $statuses[$e->event] ?? null,
                    'error' => $e->data['result']['basecamp']['error'] ?? null,
                ];
            },
            $events
        );
    }
}
