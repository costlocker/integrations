<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Invoice;
use Costlocker\Integrations\Costlocker\MarkSentInvoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CreateInvoice
{
    private $client;
    private $getUser;
    private $markSentInvoice;
    private $entityManager;

    public function __construct(FakturoidClient $c, GetUser $u, MarkSentInvoice $i, EntityManagerInterface $em)
    {
        $this->client = $c;
        $this->getUser = $u;
        $this->markSentInvoice = $i;
        $this->entityManager = $em;
    }

    public function __invoke(Request $r)
    {
        $invoice = new Invoice($this->getUser->getCostlockerUser());
        $invoice->costlockerProject = $r->request->get('costlocker')['project']['id'];
        $invoice->costlockerClient = $r->request->get('costlocker')['project']['client']['id'];
        $invoice->costlockerInvoiceId = $r->request->get('costlocker')['billing']['item']['billing_id'];
        $invoice->fakturoidSubject = $r->request->get('fakturoid')['subject'];
        $invoice->data = [
            'request' => $r->request->all(),
        ];

        $response = $this->client->__invoke(
            "/accounts/{$this->getUser->getFakturoidAccount()->slug}/invoices.json",
            [
                'custom_id' => $invoice->costlockerInvoiceId,
                'subject_id' => $invoice->fakturoidSubject,
                'lines' => array_map(
                    function (array $line) {
                        return [
                            'name' => $line['name'],
                            'unit_price' => $line['amount'],
                            'quantity' => 1,
                            'vat_rate' => 0,
                        ];
                    },
                    $r->request->get('fakturoid')['lines']
                ),
            ]
        );

        if ($response->getStatusCode() != 201) {
            return new JsonResponse(['error' => (string) $response->getBody()], 400);
        }

        $invoice->data['response'] = json_decode($response->getBody(), true);
        $invoice->fakturoidInvoiceId = $invoice->data['response']['id'];
        $invoice->fakturoidInvoiceNumber = $invoice->data['response']['number'];
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->markSentInvoice->__invoke($invoice);

        return new JsonResponse();
    }
}
