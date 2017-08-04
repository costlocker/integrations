<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Costlocker\Integrations\Sync\SyncSettings;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_projects")
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
     * @var CostlockerProject
     * @ORM\ManyToOne(targetEntity="CostlockerProject")
     * @ORM\JoinColumn(name="cl_project_id", nullable=false, onDelete="CASCADE")
     */
    public $costlockerProject;

    /**
     * @ORM\Column(name="bc_project_id", type="integer")
     */
    public $basecampProject;

    /**
     * @var BasecampUser
     * @ORM\ManyToOne(targetEntity="BasecampUser", inversedBy="projects")
     * @ORM\JoinColumn(name="bc_user_id", nullable=true, onDelete="RESTRICT")
     */
    public $basecampUser;

    /**
     * @ORM\Column(name="bc_webhook_id", type="integer", nullable=true)
     */
    public $basecampWebhook;

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

    private $syncSettings;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getSettings()
    {
        $request = new SyncSettings($this->settings);
        return $request->toArray();
    }

    public function updateSettings(array $settings)
    {
        if (!$this->syncSettings) {
            $this->syncSettings = new SyncSettings($this->settings);
        }
        $this->syncSettings->update($settings);
        $this->settings = $this->syncSettings->toArray();
    }

    public function isNotChangedSetting($setting)
    {
        return $this->syncSettings->isNotChangedSetting($setting);
    }

    public function isBasecampSynchronizationDisabled()
    {
        $isEnabled = $this->settings['areTasksEnabled'] ?? false;
        return !$isEnabled;
    }
}
