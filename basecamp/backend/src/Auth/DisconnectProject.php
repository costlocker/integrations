<?php

namespace Costlocker\Integrations\Auth;

use Doctrine\DBAL\Connection;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Queue\EventsLogger;
use Costlocker\Integrations\Database\Event;

class DisconnectProject
{
    private $db;
    private $getUser;
    private $logger;

    public function __construct(Connection $db, GetUser $u, EventsLogger $l)
    {
        $this->db = $db;
        $this->getUser = $u;
        $this->logger = $l;
    }

    public function __invoke($projectId)
    {
        $sql =<<<SQL
            UPDATE bc_project
            SET deleted_at = NOW()
            WHERE cl_project_id = :project
              AND deleted_at IS NULL
              AND cl_project_id IN (
                SELECT id
                FROM cl_project
                WHERE cl_company_id = :company
              )
            RETURNING id
SQL;
        $params = [
            'project' => $projectId,
            'company' => $this->getUser->getCostlockerUser()->costlockerCompany->id,
        ];

        $query = $this->db->executeQuery($sql, $params);
        $wasDeleted = $query->rowCount() > 0;

        $this->logger->__invoke(
            Event::DISCONNECT_PROJECT,
            $params + ['result' => $query->fetchAll(\PDO::FETCH_ASSOC)]
        );

        return $wasDeleted;
    }
}
