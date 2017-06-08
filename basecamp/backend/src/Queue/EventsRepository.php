<?php

namespace Costlocker\Integrations\Queue;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Database\Event;
use Costlocker\Integrations\Auth\GetUser;

class EventsRepository
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function findUnprocessedEvents()
    {
        $dql =<<<DQL
            SELECT e, u
            FROM Costlocker\Integrations\Database\Event e
            LEFT JOIN e.costlockerUser u
            WHERE e.event = :request AND e.updatedAt IS NULL
DQL;
        $params = [
            'request' => Event::SYNC_REQUEST,
        ];
        return $this->entityManager->createQuery($dql)->execute($params);
    }

    public function findLatestEvents()
    {
        $dql =<<<DQL
            SELECT e, u, p
            FROM Costlocker\Integrations\Database\Event e
            LEFT JOIN e.costlockerUser u
            LEFT JOIN e.basecampProject p
            LEFT JOIN p.costlockerProject pc
            WHERE (u.costlockerCompany = :company OR pc.costlockerCompany = :company)
            ORDER BY e.id DESC
DQL;
        $params = [
            'company' => $this->getUser->getCostlockerUser()->costlockerCompany->id,
        ];
        $entities = $this->entityManager
            ->createQuery($dql)
            ->setMaxResults(50)
            ->execute($params);

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
                    $description .= " #{$e->data['basecamp']}";
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
                ];
            },
            $entities
        );
    }
}
