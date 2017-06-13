<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Costlocker\RegisterWebhook;
use Costlocker\Integrations\Entities\BasecampUser;

class UpdateSettings
{
    private $entityManager;
    private $getUser;
    private $registerWebhook;

    public function __construct(EntityManagerInterface $em, GetUser $u, RegisterWebhook $r)
    {
        $this->entityManager = $em;
        $this->getUser = $u;
        $this->registerWebhook = $r;
    }

    public function __invoke(array $settings)
    {
        $company = $this->getUser->getCostlockerUser()->costlockerCompany;
        $company->settings = $settings;
        $company->defaultBasecampUser = $this->findBasecampUser($settings['account']);

        $this->registerWebhook->__invoke($company);
        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }

    private function findBasecampUser($id)
    {
        return $this->entityManager
            ->getRepository(BasecampUser::class)
            ->find((int) $id);
    }
}
