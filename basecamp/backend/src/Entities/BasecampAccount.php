<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_accounts")
 */
class BasecampAccount
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\Column(type="string")
     */
    public $product;

    /**
     * @ORM\Column(type="string")
     */
    public $urlApi;

    /**
     * @ORM\Column(type="string")
     */
    public $urlApp;

    /**
     * @ORM\ManyToOne(targetEntity="BasecampUser")
     * @ORM\JoinColumn(name="bc_user_id", nullable=false, onDelete="CASCADE")
     */
    public $basecampUser;
}
