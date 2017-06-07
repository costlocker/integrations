<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="cl_user",
 *  uniqueConstraints={
 *    @ORM\UniqueConstraint(name="cl_unique_user", columns={"email", "cl_company_id"})
 *  }
 * )
 */
class CostlockerUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $email;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerCompany")
     * @ORM\JoinColumn(name="cl_company_id", nullable=false, onDelete="CASCADE")
     */
    public $costlockerCompany;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

    /**
     * @ORM\ManyToMany(targetEntity="BasecampUser")
     * @ORM\JoinTable(name="bc_cl_users",
     *   joinColumns={@ORM\JoinColumn(name="cl_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="bc_id", referencedColumnName="id")}
     * )
     */
    public $basecampUsers;

    public function __construct()
    {
        $this->basecampUsers = new ArrayCollection();
    }

    public function addBasecampUser(BasecampUser $user)
    {
        if ($this->getUser($user->id)) {
            return;
        }
        $this->basecampUsers->add($user);
    }

    private function getUser($id)
    {
        return $this->basecampUsers
            ->filter(function (BasecampUser $u) use ($id) {
                return $u->id == $id;
            })
            ->first();
    }

    public function removeUser($id)
    {
        $user = $this->getUser($id);
        if ($user) {
            return $this->basecampUsers->removeElement($user);
        }
        return false;
    }
}
