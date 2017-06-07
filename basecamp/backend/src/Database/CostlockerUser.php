<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="cl_user",
 *  indexes={
 *    @ORM\Index(name="cl_user_tenant", columns={"id_tenant"})
 *  },
 *  uniqueConstraints={
 *    @ORM\UniqueConstraint(name="cl_unique_user", columns={"email", "id_tenant"})
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
     * @ORM\Column(type="integer")
     */
    public $idTenant;

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
}
