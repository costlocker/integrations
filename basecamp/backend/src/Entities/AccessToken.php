<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="oauth2_tokens")
 */
class AccessToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="text")
     */
    public $accessToken;

    /**
     * @ORM\Column(type="text")
     */
    public $refreshToken;

    /**
     * @ORM\Column(type="datetime")
     */
    public $expiresAt;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerUser")
     * @ORM\JoinColumn(name="cl_user_id", nullable=false, onDelete="CASCADE")
     */
    public $costlockerUser;

    /**
     * @ORM\ManyToOne(targetEntity="BasecampUser")
     * @ORM\JoinColumn(name="bc_user_id", nullable=true, onDelete="CASCADE")
     */
    public $basecampUser;
}
