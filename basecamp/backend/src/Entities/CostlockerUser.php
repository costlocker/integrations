<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @ORM\ManyToOne(targetEntity="CostlockerCompany")
     * @ORM\JoinColumn(name="cl_company_id", nullable=false, onDelete="CASCADE")
     */
    public $costlockerCompany;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

    /**
     * @var BasecampUser[]
     * @ORM\OneToMany(targetEntity="BasecampUser", mappedBy="costlockerUser")
     */
    public $basecampUsers;

    public function __construct()
    {
        $this->basecampUsers = new ArrayCollection();
    }

    public function getUser($id)
    {
        return $this->basecampUsers
            ->filter(function (BasecampUser $u) use ($id) {
                return $u->id == $id && !$u->deletedAt;
            })
            ->first();
    }
}
