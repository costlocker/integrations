<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fa_accounts")
 */
class FakturoidAccount
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
    public $slug;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\Column(type="json_array")
     */
    private $data = [
        'account' => null,
        'subjects' => [],
    ];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $subjectsDownloadedAt;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function resetSubjects()
    {
        $this->data['subjects'] = [];
    }

    public function addSubjects(array $subjects)
    {
        foreach ($subjects as $subject) {
            $this->data['subjects'][$subject['id']] = $subject;
        }
    }

    public function getSubjects()
    {
        return $this->data['subjects'];
    }

    public function setAccount(array $account)
    {
        $this->data['account'] = $account;
    }
}
