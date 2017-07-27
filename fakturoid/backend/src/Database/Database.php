<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Invoice;
use Costlocker\Integrations\Entities\CostlockerUser;
use Costlocker\Integrations\Entities\FakturoidUser;
use Costlocker\Integrations\Entities\FakturoidAccount;
use Doctrine\ORM\Query\ResultSetMapping;

/** @SuppressWarnings(PHPMD.TooManyPublicMethods) */
class Database
{
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    public function findInvoice($costlockerId)
    {
        if (!is_numeric($costlockerId)) {
            return null;
        }
        $entities = $this->entityManager
            ->getRepository(Invoice::class)
            ->findBy(
                ['costlockerInvoiceId' => $costlockerId],
                ['id' => 'desc'],
                1
            );
        return array_shift($entities);
    }

    public function findLatestInvoices(CostlockerUser $u, array $userFilters, $limit)
    {
        $filters = ['company' => ['cu.cl_company_id = :company', $u->costlockerCompany]] + $userFilters;

        $conditions = [];
        $params = [];
        foreach ($filters as $field => list($condition, $param)) {
            $conditions[] = "({$condition})";
            $params += [$field => $param];
        }
        $condition = implode(' AND ', $conditions);
        $sql =<<<SQL
            SELECT i.id, i.cl_user_id, i.fa_user_id,
                   i.data as i_data, i.fa_invoice_number, i.created_at,
                   cu.id as cu_id, cu.data as cu_data, 
                   fu.id as fu_id, fu.data as fu_data
            FROM invoices i
            JOIN cl_users cu ON i.cl_user_id = cu.id
            JOIN fa_users fu ON i.fa_user_id = fu.id
            WHERE {$condition}
            ORDER BY i.id DESC
            LIMIT {$limit}
SQL;

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult(Invoice::class, 'i');
        $rsm->addFieldResult('i', 'id', 'id');
        $rsm->addFieldResult('i', 'i_data', 'data');
        $rsm->addFieldResult('i', 'created_at', 'createdAt');
        $rsm->addFieldResult('i', 'fa_invoice_number', 'fakturoidInvoiceId');
        $rsm->addMetaResult('i', 'cl_user_id', 'costlockerUser');
        $rsm->addMetaResult('i', 'fa_user_id', 'fakturoidUser');
        $rsm->addJoinedEntityResult(CostlockerUser::class, 'cu', 'i', 'costlockerUser');
        $rsm->addFieldResult('cu', 'cu_id', 'id');
        $rsm->addFieldResult('cu', 'cu_data', 'data');
        $rsm->addJoinedEntityResult(FakturoidUser::class, 'fu', 'i', 'fakturoidUser');
        $rsm->addFieldResult('fu', 'fu_id', 'id');
        $rsm->addFieldResult('fu', 'fu_data', 'data');
        $query = $this->entityManager->createNativeQuery($sql, $rsm);
        return $query->execute($params);
    }

    public function findLatestSubjectForClient($costlockerClientId)
    {
        $sql =<<<SQL
            SELECT fa_subject_id
            FROM invoices
            WHERE cl_client_id = :id
            ORDER BY id DESC
            LIMIT 1
SQL;
        $params = [
            'id' => (int) $costlockerClientId,
        ];
        return $this->entityManager->getConnection()->executeQuery($sql, $params)->fetchColumn();
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
