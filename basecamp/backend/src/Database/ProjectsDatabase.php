<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Basecamp\SyncDatabase;
use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Database\CostlockerProject;
use Costlocker\Integrations\Database\BasecampAccount;

class ProjectsDatabase implements SyncDatabase
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function findProject($costockerProjectId)
    {
        $projects = $this->findProjects($costockerProjectId);
        return reset($projects);
    }

    public function upsertProject($costockerProjectId, array $mapping, array $settings = [])
    {
        $costlockerProject = $this->entityManager->getRepository(CostlockerProject::class)
            ->find($costockerProjectId) ?: new CostlockerProject();
        $costlockerProject->id = $costockerProjectId;

        if ($this->getUser->getCostlockerUser(false)) {
            $costlockerProject->costlockerCompany = $this->getUser->getCostlockerUser()->costlockerCompany;
        }
        
        $basecampProject = $costlockerProject->upsertProject($mapping['id']);
        $basecampProject->mapping = $mapping['activities'];
        $basecampProject->settings = $settings;
        $basecampProject->basecampAccount = $this->entityManager
            ->getRepository(BasecampAccount::class)
            ->find($mapping['account']['id']);

        $this->entityManager->persist($costlockerProject);
        $this->entityManager->persist($basecampProject);
        $this->entityManager->flush();

        return $basecampProject;
    }

    public function findProjects($costlockerProjectId)
    {
        $dql =<<<DQL
            SELECT bp, ba
            FROM Costlocker\Integrations\Database\BasecampProject bp
            JOIN bp.costlockerProject cp
            JOIN bp.basecampAccount ba
            WHERE cp.id = :project
              AND bp.deletedAt IS NULL
            ORDER BY bp.id DESC
DQL;
        $params = [
            'project' => $costlockerProjectId,
        ];

        $entities = $this->entityManager->createQuery($dql)
            ->setMaxResults(1)
            ->execute($params);

        return array_map(
            function (BasecampProject $p) {
                return [
                    'id' => $p->basecampProject,
                    'activities' => $p->mapping,
                    'settings' => $p->settings,
                    'account' => [
                        'id' => $p->basecampAccount->id,
                        'product' => $p->basecampAccount->product,
                        'href' => $p->basecampAccount->urlApi,
                    ],
                ];
            },
            $entities
        );
    }

    public function deleteProject($costlockerProjectId, $basecampProjectId)
    {
        $sql =<<<SQL
            UPDATE bc_project
            SET deleted_at = NOW()
            WHERE cl_project_id = :cl AND bc_project_id = :bc
SQL;
        $params = [
            'cl' => $costlockerProjectId,
            'bc' => $basecampProjectId,
        ];
        $this->entityManager->getConnection()->executeQuery($sql, $params);
    }
}
