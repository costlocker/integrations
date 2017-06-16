<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Costlocker\Integrations\Sync\SyncSettings;

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

    /**
     * @var BasecampUser
     * @ORM\ManyToOne(targetEntity="BasecampUser")
     * @ORM\JoinColumn(name="bc_user_id", nullable=true, onDelete="SET NULL")
     */
    public $defaultBasecampUser;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerUser")
     * @ORM\JoinColumn(name="cl_user_id", nullable=true, referencedColumnName="id", onDelete="SET NULL")
     */
    public $defaultCostlockerUser;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->projects = new ArrayCollection();
    }

    public function getSettings()
    {
        $request = new SyncSettings($this->settings);
        return [
            'isCreatingBasecampProjectEnabled' => $this->isCreatingBasecampProjectEnabled(),
            'account' => $this->defaultBasecampUser ? $this->defaultBasecampUser->id : null,
            'costlockerUser' => $this->defaultCostlockerUser ? $this->defaultCostlockerUser->email : null,
            'isCostlockerWebhookEnabled' => $this->urlWebhook ? true : false,
        ] + $request->toArray();
    }

    public function isCreatingBasecampProjectEnabled()
    {
        return $this->defaultBasecampUser
            && $this->defaultBasecampUser->isActive()
            && $this->defaultCostlockerUser
            && ($this->settings['isCreatingBasecampProjectEnabled'] ?? false);
    }
}
