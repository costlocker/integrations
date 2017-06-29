<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class CreateInvoice
{
    private $client;
    private $getUser;
    private $entityManager;

    public function __construct(FakturoidClient $c, GetUser $u, EntityManagerInterface $em)
    {
        $this->client = $c;
        $this->getUser = $u;
        $this->entityManager = $em;
    }

    public function __invoke(Request $r)
    {
        $response = $this->client->__invoke(
            "/accounts/{$this->getUser->getFakturoidAccount()->slug}/invoices.json",
            [
                'custom_id' => $r->request->get('costlocker')['invoice']['item']['billing_id'],
                'subject_id' => $r->request->get('subject'),
                'lines' => array_map(
                    function (array $line) {
                        return [
                            'name' => $line['name'],
                            'unit_price' => $line['amount'],
                            'quantity' => 1,
                            'vat_rate' => 0,
                        ];
                    },
                    $r->request->get('lines')
                ),
            ]
        );
        return json_decode($response->getBody(), true);
    }
}
