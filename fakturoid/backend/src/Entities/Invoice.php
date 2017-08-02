<?php

namespace Costlocker\Integrations\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="invoices",indexes={
 *  @ORM\Index(name="invoices_cl_project_id_idx", columns={"cl_project_id"}),
 *  @ORM\Index(name="invoices_cl_client_id_idx", columns={"cl_client_id"}),
 *  @ORM\Index(name="invoices_fa_subject_id_idx", columns={"fa_subject_id"}),
 * })
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="integer", name="cl_invoice_id")
     */
    public $costlockerInvoiceId;

    /**
     * @ORM\Column(type="integer", name="fa_invoice_id")
     */
    public $fakturoidInvoiceId;

    /**
     * @ORM\Column(type="string", name="fa_invoice_number")
     */
    public $fakturoidInvoiceNumber;

    /**
     * @ORM\Column(type="integer", name="cl_project_id")
     */
    public $costlockerProject;

    /**
     * @ORM\Column(type="integer", name="cl_client_id")
     */
    public $costlockerClient;

    /**
     * @ORM\Column(type="integer", name="fa_subject_id")
     */
    public $fakturoidSubject;

    /**
     * @ORM\ManyToOne(targetEntity="CostlockerUser")
     * @ORM\JoinColumn(name="cl_user_id", nullable=false, referencedColumnName="id", onDelete="RESTRICT")
     */
    public $costlockerUser;

    /**
     * @ORM\ManyToOne(targetEntity="FakturoidUser")
     * @ORM\JoinColumn(name="fa_user_id", nullable=false, referencedColumnName="id", onDelete="RESTRICT")
     */
    public $fakturoidUser;

    /**
     * @ORM\Column(type="json_array")
     */
    public $data;

    /**
     * @ORM\Column(type="datetime")
     */
    public $createdAt;

    public function __construct(CostlockerUser $u = null)
    {
        $this->createdAt = new \DateTime();
        if ($u) {
            $this->costlockerUser = $u;
            $this->fakturoidUser = $u->fakturoidUser;
        }
    }

    public function getCurrentCostlockerDescription()
    {
        return $this->data['request']['costlocker']['billing']['billing']['description'] ?? '';
    }

    public function getCurrentCostlockerAmount()
    {
        return ((float) $this->data['response']['subtotal']) ?? null;
    }

    public function getIssuedDate()
    {
        return $this->data['response']['issued_on'] ?? '';
    }

    public function getUpdateStatus()
    {
        return [
            'hasFailed' => $this->data['updateCostlocker']['hasFailed'] ?? false,
            'error' => $this->data['updateCostlocker']['response']['errors'][0]['detail'] ?? '',
        ];
    }
}
