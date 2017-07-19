<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="cl_users",
 *  uniqueConstraints={
 *    @ORM\UniqueConstraint(name="cl_unique_user", columns={"email", "cl_company_id"})
 *  }
 * )
 */
class CostlockerUser
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
    public $email;

    /**
     * @ORM\Column(type="integer", name="cl_company_id")
     */
    public $costlockerCompany;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

    /**
     * @var FakturoidUser
     * @ORM\ManyToOne(targetEntity="FakturoidUser")
     * @ORM\JoinColumn(name="fa_user_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     */
    public $fakturoidUser;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
