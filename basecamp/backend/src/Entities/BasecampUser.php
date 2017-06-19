<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_cl_users")
 */
class BasecampUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="integer", name="bc_identity_id")
     */
    public $basecampIdentityId;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerUser", inversedBy="basecampUsers")
     * @ORM\JoinColumn(name="cl_user_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    public $costlockerUser;

    /**
     * @ORM\ManyToOne(targetEntity="BasecampAccount")
     * @ORM\JoinColumn(name="bc_account_id", nullable=false, referencedColumnName="id", onDelete="CASCADE")
     */
    public $basecampAccount;

    /**
     * @ORM\OneToMany(targetEntity="BasecampProject", mappedBy="basecampUser")
     */
    public $projects;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $deletedAt;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    public function isActive()
    {
        return !$this->deletedAt;
    }
}
