<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="bc_users")
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
     * @ORM\OneToMany(targetEntity="BasecampAccount", mappedBy="basecampUser", cascade={"persist"})
     */
    public $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }

    public function upsertAccount($id)
    {
        $account = $this->getAccount($id);
        if ($account) {
            return $account;
        }

        $newAccount = new BasecampAccount();
        $newAccount->id = $id;
        $newAccount->basecampUser = $this;
        $this->accounts->add($newAccount);
        return $newAccount;
    }

    public function getAccount($id)
    {
        $accounts = $this->accounts->filter(function (BasecampAccount $ac) use ($id) {
            return $ac->id == $id;
        });
        return $accounts->first();
    }
}
