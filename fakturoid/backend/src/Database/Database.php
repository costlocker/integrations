<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Invoice;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\FakturoidAccount;

class Database
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function findInvoice($costlockerId)
    {
        $entities = $this->entityManager
            ->getRepository(Invoice::class)
            ->findBy(
                ['costlockerInvoiceId' => $costlockerId],
                ['id' => 'desc'],
                1
            );
        return array_shift($entities);
    }

    public function findCostlockerUserById($id)
    {
        $dql =<<<DQL
            SELECT cu, fu, fa
            FROM Costlocker\Integrations\Entities\CostlockerUser cu
            LEFT JOIN cu.fakturoidUser fu
            LEFT JOIN fu.fakturoidAccount fa
            WHERE cu.id = :id
DQL;
        $params = [
            'id' => $id,
        ];
        $entities = $this->entityManager->createQuery($dql)->execute($params);
        return array_shift($entities);
    }

    public function findCostlockerUser($email, $companyId)
    {
        return $this->entityManager
            ->getRepository(CostlockerUser::class)
            ->findOneBy(['email' => $email, 'costlockerCompany' => $companyId]);
    }

    public function findFakturoidAccount($slug)
    {
        return $this->entityManager
            ->getRepository(FakturoidAccount::class)
            ->findOneBy(['slug' => $slug]);
    }

    public function findFakturoidUser($email, $slug)
    {
        $dql =<<<DQL
            SELECT u
            FROM Costlocker\Integrations\Entities\FakturoidUser u
            JOIN u.fakturoidAccount a
            WHERE u.email = :email AND a.slug = :slug
DQL;
        $params = [
            'email' => $email,
            'slug' => $slug,
        ];
        $entites = $this->entityManager->createQuery($dql)->execute($params);
        return array_shift($entites);
    }

    public function persist()
    {
        foreach (func_get_args() as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }
}
