<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fa_accounts")
 */
class FakturoidAccount
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $slug;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
