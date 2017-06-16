<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Entities\CostlockerProject;
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

    public function upsertProject($costockerProjectId, array $update)
    {
        $costlockerProject = $this->entityManager->getRepository(CostlockerProject::class)
            ->find($costockerProjectId) ?: new CostlockerProject();
        $costlockerProject->id = $costockerProjectId;

        if ($this->getUser->getCostlockerUser(false)) {
            $costlockerProject->costlockerCompany = $this->getUser->getCostlockerUser()->costlockerCompany;
        }
        
        $basecampProject = $costlockerProject->upsertProject($update['id']);
        $basecampProject->mapping = $update['activities'];
        $basecampProject->updateSettings($update['settings']);
        $basecampProject->basecampUser = $this->entityManager
            ->getRepository(BasecampUser::class)
            ->find($update['account']);

        $this->entityManager->persist($costlockerProject);
        $this->entityManager->persist($basecampProject);
        $this->entityManager->flush();

        return $basecampProject;
    }

    public function findByCostlockerId($id)
    {
        return $this->findProjectEntity('costlockerProject', $id);
    }

    public function findByBasecampId($id)
    {
        return $this->findProjectEntity('basecampProject', $id);
    }

    public function findAll()
    {
        $dql = $this->createDql();
        $entities = $this->entityManager->createQuery($dql)->execute();
        $indexed = [];
        foreach ($entities as $project) {
            $indexed[$project->costlockerProject->id] = $project;
        }
        return $indexed;
    }

    private function findProjectEntity($column, $projectId)
    {
        $dql = $this->createDql("bp.{$column} = :project AND");
        $params = [
            'project' => $projectId,
        ];

        $entities = $this->entityManager->createQuery($dql)
            ->setMaxResults(1)
            ->execute($params);
        return array_shift($entities);
    }

    private function createDql($extraCondition = '')
    {
        return <<<DQL
            SELECT bp, bu, ba
            FROM Costlocker\Integrations\Entities\BasecampProject bp
            JOIN bp.costlockerProject cp
            JOIN bp.basecampUser bu
            JOIN bu.basecampAccount ba
            WHERE {$extraCondition} bp.deletedAt IS NULL AND bu.deletedAt IS NULL
            ORDER BY bp.id DESC
DQL;
    }

    /** @return CostlockerCompany */
    public function findCompanyByWebhook($webhookUrl)
    {
        if (!$webhookUrl) {
            return null;
        }
        return $this->entityManager->getRepository(CostlockerCompany::class)
            ->findOneBy(['urlWebhook' => $webhookUrl]);
    }
}
