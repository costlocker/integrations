<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="events")
 */
class Event
{
    const WEBHOOK_SYNC = 0x10;
    const MANUAL_SYNC = 0x20;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="BasecampProject")
     * @ORM\JoinColumn(name="bc_project_id", nullable=true, onDelete="CASCADE")
     */
    public $basecampProject;

    /**
     * @ORM\Column(type="integer")
     */
    public $event;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

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
        $this->updatedAt = new \DateTime();
    }
}
