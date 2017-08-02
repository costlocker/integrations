<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Entities\Invoice;

class MarkSentInvoice
{
    private $client;

    public function __construct(CostlockerClient $c)
    {
        $this->client = $c;
    }

    public function __invoke(Invoice $invoice)
    {
        $request = [
            'id' => $invoice->costlockerProject,
            'items' => [
                $this->getBillingItem($invoice),
            ],
        ];
        $response = $this->client->__invoke('/projects', $request);

        $invoice->data['updateCostlocker'] = [
            'hasFailed' => $response ->getStatusCode() != 200,
            'request' => $request,
            'response' => json_decode((string) $response->getBody(), true),
        ];
        $invoice->costlockerInvoiceId =
            $invoice->data['updateCostlocker']['response']['data'][0]['items'][0]['item']['billing_id'] ?? -1;
    }

    private function getBillingItem(Invoice $invoice)
    {
        $item = [];
        if (!$invoice->costlockerInvoiceId) {
            $item = [
                'type' => 'billing',
            ];
        } else {
            $item = [
                'type' => 'billing',
                'billing_id' => $invoice->costlockerInvoiceId,
            ];
        }
        return [
            'item' => $item,
            'billing' => [
                'description' => $invoice->getCurrentCostlockerDescription() ?: $invoice->fakturoidInvoiceNumber,
                'status' => 'sent',
                'date' => $invoice->getIssuedDate(),
                'total_amount' => $invoice->getCurrentCostlockerAmount(),
            ],
        ];
    }
}
