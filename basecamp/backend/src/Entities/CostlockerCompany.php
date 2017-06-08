<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="cl_companies")
 */
class CostlockerCompany
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    public $settings;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public $urlWebhook;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="CostlockerProject", mappedBy="costlockerCompany")
     */
    public $projects;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->projects = new ArrayCollection();
    }

    public function getSettings()
    {
        $request = new \Costlocker\Integrations\Sync\SyncRequest();
        return [
            'isCostlockerWebhookEnabled' => $this->urlWebhook ? true : false
        ] + ($this->settings ?: []) + $request->toSettings();
    }
}
