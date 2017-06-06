<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_user")
 */
class BasecampUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerUser")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    public $costlockerUser;

    /**
     * @ORM\OneToMany(targetEntity="BasecampAccount", mappedBy="basecampUser", cascade={"persist"})
     */
    public $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }

    public function addAccount(BasecampAccount $account)
    {
        if ($this->getAccount($account->id)) {
            return;
        }
        $this->accounts->add($account);
        $account->basecampUser = $this;
    }

    public function getAccount($idAccount)
    {
        $accounts = $this->accounts->filter(function (BasecampAccount $ac) use ($idAccount) {
            return $ac->id == $idAccount;
        });
        return $accounts->first();
    }
}
