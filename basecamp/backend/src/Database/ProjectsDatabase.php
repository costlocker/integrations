<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\CostlockerProject;
use Costlocker\Integrations\Entities\BasecampProject;
use Costlocker\Integrations\Entities\BasecampUser;
use Costlocker\Integrations\Sync\SyncDatabase;

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
        $basecampProject->basecampUser = $this->entityManager
            ->getRepository(BasecampUser::class)
            ->find($mapping['account']);

        $this->entityManager->persist($costlockerProject);
        $this->entityManager->persist($basecampProject);
        $this->entityManager->flush();

        return $basecampProject;
    }

    public function findBasecampProject($costlockerProjectId)
    {
        return $this->findProjectEntity('cl_project_id', $costlockerProjectId);
    }

    public function findBasecampProjectById($basecampProjectId)
    {
        return $this->findProjectEntity('bc_project_id', $basecampProjectId);
    }

    private function findProjectEntity($column, $projectId)
    {
        $dql =<<<DQL
            SELECT bp, bu, ba
            FROM Costlocker\Integrations\Entities\BasecampProject bp
            JOIN bp.costlockerProject cp
            JOIN bp.basecampUser bu
            JOIN bu.basecampAccount ba
            WHERE bp.{$column} = :project
              AND bp.deletedAt IS NULL AND bu.deletedAt IS NULL
            ORDER BY bp.id DESC
DQL;
        $params = [
            'project' => $projectId,
        ];

        $entities = $this->entityManager->createQuery($dql)
            ->setMaxResults(1)
            ->execute($params);
        return array_shift($entities);
    }

    public function findProjects($costlockerProjectId)
    {
        $entity = $this->findBasecampProject($costlockerProjectId);
        return array_map(
            function (BasecampProject $p) {
                return [
                    'id' => $p->basecampProject,
                    'activities' => $p->mapping,
                    'settings' => $p->settings,
                    'account' => [
                        'id' => $p->basecampUser->id,
                        'basecampId' => $p->basecampUser->basecampAccount->id,
                        'name' => $p->basecampUser->basecampAccount->name,
                        'product' => $p->basecampUser->basecampAccount->product,
                        'href' => $p->basecampUser->basecampAccount->urlApi,
                        'identity' => $p->basecampUser->data,
                    ],
                ];
            },
            $entity ? [$entity] : []
        );
    }
}
