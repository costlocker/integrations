<?php

namespace Costlocker\Integrations\Auth;

use Doctrine\DBAL\Connection;
use Costlocker\Integrations\Auth\GetUser;

class DisconnectProject
{
    private $db;
    private $getUser;

    public function __construct(Connection $db, GetUser $u)
    {
        $this->db = $db;
        $this->getUser = $u;
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
SQL;
        $params = [
            'project' => $projectId,
            'company' => $this->getUser->getCostlockerUser()->costlockerCompany->id,
        ];

        $query = $this->db->executeQuery($sql, $params);
        return $query->rowCount() > 0;
    }
}
