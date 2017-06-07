<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cl_project")
 */
class CostlockerProject
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerCompany")
     * @ORM\JoinColumn(name="cl_company_id", nullable=false, onDelete="CASCADE")
     */
    public $costlockerCompany;

    /**
     * @ORM\OneToMany(targetEntity="BasecampProject", mappedBy="costlockerProject")
     */
    public $projects;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->projects = new ArrayCollection();
    }
}
