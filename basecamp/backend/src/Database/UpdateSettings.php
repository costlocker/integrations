<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\CostlockerCompany;

class UpdateSettings
{
    private $entityManager;
    private $getUser;

    public function __construct(EntityManagerInterface $em, GetUser $u)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
    }

    public function __invoke(array $settings)
    {
        $company = $this->getUser->getCostlockerUser()->costlockerCompany;
        $company->settings = $settings;
        $company->defaultBasecampUser = $this->findBasecampUser($company, $settings['account']);
        $company->defaultCostlockerUser = $this->findCostlockerUser($company, $settings['costlockerUser']);

        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }

    private function findBasecampUser(CostlockerCompany $c, $id)
    {
        if (!$id) {
            return null;
        }
        $dql =<<<DQL
            SELECT b
            FROM Costlocker\Integrations\Entities\BasecampUser b
            JOIN b.costlockerUser c
            WHERE b.id = :id AND c.costlockerCompany = :company
DQL;
        $params = [
            'id' => $id,
            'company' => $c->id,
        ];
        $entities = $this->entityManager->createQuery($dql)->execute($params);
        return reset($entities) ?: null;
    }

    private function findCostlockerUser(CostlockerCompany $c, $email)
    {
        if (!$email) {
            return null;
        }
        return $this->entityManager
            ->getRepository(CostlockerUser::class)
            ->findOneBy(['costlockerCompany' => $c->id, 'email' => $email]);
    }
}
