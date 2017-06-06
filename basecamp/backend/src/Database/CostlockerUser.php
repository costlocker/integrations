<?php

namespace Costlocker\Integrations\Database;

use Doctrine\ORM\Mapping as ORM;

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
}
