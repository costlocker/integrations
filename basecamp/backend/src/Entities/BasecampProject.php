<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_project")
 */
class BasecampProject
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerProject")
     * @ORM\JoinColumn(name="cl_project_id", nullable=false, onDelete="CASCADE")
     */
    public $costlockerProject;

    /**
     * @ORM\Column(name="bc_project_id", type="integer")
     */
    public $basecampProject;

    /**
     * @var BasecampAccount
     * @ORM\ManyToOne(targetEntity="BasecampAccount")
     * @ORM\JoinColumn(name="bc_account_id", nullable=false, onDelete="RESTRICT")
     */
    public $basecampAccount;

    /**
     * @ORM\Column(type="json_array")
     */
    public $settings;

    /**
     * @ORM\Column(type="json_array")
     */
    public $mapping;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $deletedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }
}
