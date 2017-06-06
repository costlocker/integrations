<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_account")
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
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    public $basecampUser;
}
