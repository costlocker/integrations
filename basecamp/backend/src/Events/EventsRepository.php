<?php

namespace Costlocker\Integrations\Events;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Event;
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
            FROM Costlocker\Integrations\Entities\Event e
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
            FROM Costlocker\Integrations\Entities\Event e
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

        $converter = new EventsToJson();
        return $converter($entities);
    }
}
