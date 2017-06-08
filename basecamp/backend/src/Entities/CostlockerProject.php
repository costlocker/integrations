<?php

namespace Costlocker\Integrations\Entities;

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
     * @var BasecampProject[]
     * @ORM\OneToMany(targetEntity="BasecampProject", mappedBy="costlockerProject", cascade={"persist"})
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

    public function upsertProject($basecampProjectId)
    {
        foreach ($this->projects as $project) {
            if ($project->basecampProject == $basecampProjectId && !$project->deletedAt) {
                return $project;
            }
        }

        $newProject = new BasecampProject();
        $newProject->costlockerProject = $this;
        $newProject->basecampProject = $basecampProjectId;
        $this->projects->add($newProject);
        return $newProject;
    }
}
