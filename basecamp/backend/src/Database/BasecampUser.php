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
     * @ORM\ManyToMany(targetEntity="CostlockerUser")
     * @ORM\JoinTable(name="bc_cl_users",
     *   joinColumns={@ORM\JoinColumn(name="bc_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="cl_id", referencedColumnName="id")}
     * )
     */
    public $costlockerUsers;

    /**
     * @ORM\OneToMany(targetEntity="BasecampAccount", mappedBy="basecampUser", cascade={"persist"})
     */
    public $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->costlockerUsers = new ArrayCollection();
    }

    public function addAccount(BasecampAccount $account)
    {
        if ($this->getAccount($account->id)) {
            return;
        }
        $this->accounts->add($account);
        $account->basecampUser = $this;
    }

    public function getAccount($id)
    {
        $accounts = $this->accounts->filter(function (BasecampAccount $ac) use ($id) {
            return $ac->id == $id;
        });
        return $accounts->first();
    }

    public function addCostlockerUser(CostlockerUser $user)
    {
        if ($this->getCostlockerUser($user->id)) {
            return;
        }
        $this->costlockerUsers->add($user);
    }

    private function getCostlockerUser($id)
    {
        $accounts = $this->accounts->filter(function (CostlockerUser $u) use ($id) {
            return $u->id == $id;
        });
        return $accounts->first();
    }
}
