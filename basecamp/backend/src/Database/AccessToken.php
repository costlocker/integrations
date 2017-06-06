<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="apitoken")
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
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    public $costlockerUser;

    /**
     * @ORM\ManyToOne(targetEntity="BasecampUser")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    public $basecampUser;
}
