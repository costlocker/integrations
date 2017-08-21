<?php

namespace Costlocker\Integrations\Database;

use Costlocker\Integrations\Auth\GetUser;
use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\CostlockerCompany;
use Costlocker\Integrations\Entities\CostlockerProject;
use Costlocker\Integrations\Entities\BasecampProject;
use Costlocker\Integrations\Entities\BasecampUser;
use Costlocker\Integrations\Sync\SyncDatabase;
use Costlocker\Integrations\Sync\SyncResponse;

class ProjectsDatabase implements SyncDatabase
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function upsertProject(SyncResponse $result)
    {
        $costlockerProject = $this->entityManager->getRepository(CostlockerProject::class)
            ->find($result->costlockerChangelog->projectId) ?: new CostlockerProject();
        $costlockerProject->id = $result->costlockerChangelog->projectId;

        if ($this->getUser->getCostlockerUser(false)) {
            $costlockerProject->costlockerCompany = $this->getUser->getCostlockerUser()->costlockerCompany;
        }
        
        $basecampProject = $costlockerProject->upsertProject($result->basecampChangelog->projectId);
        $result->oldMapping = $basecampProject->mapping;
        $basecampProject->mapping = $result->newMapping;
        if ($result->request->isCompleteProjectSynchronized) {
            $basecampProject->updateSettings($result->getSettings());
        }
        $basecampProject->basecampUser = $this->entityManager
            ->getRepository(BasecampUser::class)
            ->find($result->request->account);

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

    public function findByInternalId($id)
    {
        if (!$id) {
            return null;
        }
        return $this->findProjectEntity('id', $id, true);
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

    private function findProjectEntity($column, $projectId, $canBeDeleted = false)
    {
        $dql = $this->createDql("bp.{$column} = :project AND", $canBeDeleted);
        $params = [
            'project' => $projectId,
        ];

        $entities = $this->entityManager->createQuery($dql)
            ->setMaxResults(1)
            ->execute($params);
        return array_shift($entities);
    }

    private function createDql($extraCondition = '', $canBeDeleted = false)
    {
        $deletedCondition = $canBeDeleted ? '1=1' : 'bp.deletedAt IS NULL AND bu.deletedAt IS NULL';
        return <<<DQL
            SELECT bp, bu, ba
            FROM Costlocker\Integrations\Entities\BasecampProject bp
            JOIN bp.costlockerProject cp
            JOIN bp.basecampUser bu
            JOIN bu.basecampAccount ba
            WHERE {$extraCondition} {$deletedCondition}
            ORDER BY bp.id DESC
DQL;
    }

    /** @return CostlockerCompany */
    public function findCompanyByWebhook($webhookUrl)
    {
        $parsedUrl = parse_url($webhookUrl ?: 'http://example.com/');
        if (!$webhookUrl || is_bool(strpos($parsedUrl['host'], 'costlocker'))) {
            return null;
        }
        $dql =<<<DQL
            SELECT c
            FROM Costlocker\Integrations\Entities\CostlockerCompany c
            WHERE c.urlWebhook LIKE :path AND c.urlWebhook LIKE :scheme
DQL;
        $params = [
            'scheme' => "{$parsedUrl['scheme']}%",
            'path' => "%{$parsedUrl['path']}",
        ];
        $entities =  $this->entityManager->createQuery($dql)->execute($params);
        return array_shift($entities);
    }

    public function undoDisconnect(BasecampProject $p)
    {
        $p->deletedAt = null;
        $this->entityManager->persist($p);
        $this->entityManager->flush();
    }
}
