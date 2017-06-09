<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

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
     * @var BasecampUser[]
     * @ORM\OneToMany(targetEntity="BasecampUser", mappedBy="basecampAccount")
     */
    public $costlockerUsers;

    public function __construct()
    {
        $this->costlockerUsers = new ArrayCollection();
    }
}
