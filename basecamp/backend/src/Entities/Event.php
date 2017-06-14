<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="events")
 */
class Event
{
    const SYNC_REQUEST = 0x00;
    const WEBHOOK_SYNC = 0x10;
    const MANUAL_SYNC = 0x20;
    const DISCONNECT_BASECAMP = 0x40;
    const DISCONNECT_PROJECT = 0x80;
    const REGISTER_WEBHOOK = 0x100;

    const RESULT_SUCCESS = 0x1;
    const RESULT_FAILURE = 0x2;
    const RESULT_NOCHANGE = 0x4;
    const RESULT_PARTIAL_SUCCESS = 0x8;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var CostlockerUser
     * @ORM\ManyToOne(targetEntity="CostlockerUser")
     * @ORM\JoinColumn(name="cl_user_id", nullable=true, onDelete="SET NULL")
     */
    public $costlockerUser;

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
    }

    public function markStatus($status, $result)
    {
        $this->event |= $status;
        $this->data['result'] = $result;
        $this->updatedAt = new \DateTime();
    }
}
