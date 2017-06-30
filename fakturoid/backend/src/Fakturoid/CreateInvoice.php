<?php

namespace Costlocker\Integrations\Fakturoid;

use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Invoice;
use Costlocker\Integrations\Costlocker\MarkSentInvoice;
use Costlocker\Integrations\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CreateInvoice
{
    private $client;
    private $getUser;
    private $markSentInvoice;
    private $database;

    public function __construct(FakturoidClient $c, GetUser $u, MarkSentInvoice $i, Database $dm)
    {
        $this->client = $c;
        $this->getUser = $u;
        $this->markSentInvoice = $i;
        $this->database = $dm;
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
        unset($invoice->data['request']['costlocker']['project']['budget']);

        $response = $this->client->__invoke(
            "/accounts/{$this->getUser->getFakturoidAccount()->slug}/invoices.json",
            [
                'custom_id' => $invoice->costlockerInvoiceId,
                'subject_id' => $invoice->fakturoidSubject,
                'lines' => array_values(array_filter(array_map(
                    function (array $line) {
                        if ($line['quantity'] <= 0) {
                            return null;
                        }
                        return [
                            'name' => $line['name'],
                            'unit_price' => $line['unit_amount'],
                            'quantity' => $line['quantity'],
                            'unit_name' => $line['unit'],
                            'vat_rate' => 0,
                        ];
                    },
                    $r->request->get('fakturoid')['lines']
                ))),
            ]
        );

        if ($response->getStatusCode() != 201) {
            return new JsonResponse(['error' => (string) $response->getBody()], 400);
        }

        $invoice->data['response'] = json_decode($response->getBody(), true);
        $invoice->fakturoidInvoiceId = $invoice->data['response']['id'];
        $invoice->fakturoidInvoiceNumber = $invoice->data['response']['number'];
        $this->database->persist($invoice);

        $this->markSentInvoice->__invoke($invoice);

        return new JsonResponse();
    }
}
