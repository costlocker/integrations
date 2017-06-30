<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\EntityManagerInterface;
use Costlocker\Integrations\Entities\Invoice;

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
}
