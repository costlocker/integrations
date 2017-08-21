<?php

namespace Costlocker\Integrations\Events;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Event;
use Costlocker\Integrations\Auth\GetUser;

class EventsRepository
{
    private $entityManager;
    private $getUser;
    private $converter;

    public function __construct(EntityManagerInterface $em, GetUser $u, EventsToJson $c)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
        $this->converter = $c;
    }

    public function findUnprocessedEvents()
    {
        $sql =<<<SQL
            SELECT e.id
            FROM events e
            WHERE e.event = :request AND e.updated_at IS NULL
SQL;
        $params = [
            'request' => Event::SYNC_REQUEST,
        ];
        return $this->entityManager->getConnection()->executeQuery($sql, $params)
            ->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findBasecampWebhooks($secondsDelay = 5)
    {
        $sql =<<<SQL
            SELECT p.cl_project_id as id,
                   array_to_json(array_agg(e.id)) as events,
                   array_to_json(array_agg(e.data)) as data,
                   min(e.created_at) as min_event_date,
                   max(e.created_at) as max_event_date
            FROM events e
            JOIN bc_projects p ON e.bc_project_id = p.id
            WHERE e.event = :event
              AND e.created_at < NOW() - INTERVAL '{$secondsDelay} seconds'
              AND e.updated_at IS NULL
            GROUP BY p.cl_project_id
SQL;
        $params = [
            'event' => Event::WEBHOOK_BASECAMP,
        ];
        return $this->entityManager->getConnection()->executeQuery($sql, $params)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function markEventsAsProcessed(array $ids)
    {
        $sql =<<<SQL
            UPDATE events
            SET updated_at = NOW()
            WHERE id IN (:ids)
SQL;
        $params = [
            'ids' => $ids,
        ];
        $types = [
            'ids' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
        ];
        return $this->entityManager->getConnection()->executeQuery($sql, $params, $types);
    }

    public function findSyncRequest($eventId)
    {
        $dql =<<<DQL
            SELECT e, u
            FROM Costlocker\Integrations\Entities\Event e
            LEFT JOIN e.costlockerUser u
            WHERE e.event = :request AND e.id = :id
DQL;
        $params = [
            'request' => Event::SYNC_REQUEST,
            'id' => $eventId
        ];
        $entities = $this->entityManager->createQuery($dql)->execute($params);
        return array_shift($entities);
    }

    public function findLatestEvents($costlockerProjectId)
    {
        $filter = '';
        $filterParams = [];
        if ($costlockerProjectId) {
            $filter = 'pc.id = :id';
            $filterParams = ['id' => $costlockerProjectId];
        } else {
            $filter = 'e.event <> :ignoredEvent AND p.deletedAt IS NULL';
            $filterParams = ['ignoredEvent' => Event::WEBHOOK_BASECAMP];
        }
        $dql =<<<DQL
            SELECT e, u, p
            FROM Costlocker\Integrations\Entities\Event e
            LEFT JOIN e.costlockerUser u
            LEFT JOIN e.basecampProject p
            LEFT JOIN p.costlockerProject pc
            WHERE (u.costlockerCompany = :company OR pc.costlockerCompany = :company)
              AND {$filter}
            ORDER BY e.id DESC
DQL;
        $params = [
            'company' => $this->getUser->getCostlockerUser()->costlockerCompany->id,
        ] + $filterParams;
        $entities = $this->entityManager
            ->createQuery($dql)
            ->setMaxResults(50)
            ->execute($params);

        return $this->converter->__invoke($entities);
    }
}
