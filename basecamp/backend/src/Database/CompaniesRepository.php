<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\CostlockerCompany;

class CompaniesRepository
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
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
