<?php

namespace Costlocker\Integrations\Costlocker;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;

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
        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }
}
