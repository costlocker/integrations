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
            'request' => $request,
            'response' => json_decode((string) $response->getBody(), true),
        ];
        $invoice->costlockerInvoiceId =
            $invoice->data['updateCostlocker']['response']['data'][0]['items'][0]['item']['billing_id'] ?? -1;
    }

    private function getBillingItem(Invoice $invoice)
    {
        $description = $invoice->getCurrentCostlockerDescription() ?: $invoice->fakturoidInvoiceNumber;
        if (!$invoice->costlockerInvoiceId) {
            return [
                'item' => [
                    'type' => 'billing',
                ],
                'billing' => [
                    'description' => $description,
                    'status' => 'sent',
                    'date' => $invoice->getIssuedDate(),
                    'total_amount' => $invoice->getCurrentCostlockerAmount(),
                ],
            ];
        }
        return [
            'item' => [
                'type' => 'billing',
                'billing_id' => $invoice->costlockerInvoiceId,
            ],
            'billing' => [
                'description' => $description,
                'status' => 'sent',
                'date' => $invoice->getIssuedDate(),
            ],
        ];
    }
}
