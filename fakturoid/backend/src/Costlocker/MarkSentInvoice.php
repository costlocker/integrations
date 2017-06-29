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
        $description = $invoice->getCurrentCostlockerDescription() ?: $invoice->fakturoidInvoiceNumber;
        $this->client->__invoke(
            '/projects',
            [
                'id' => $invoice->costlockerProject,
                'items' => [
                    [
                        'item' => [
                            'type' => 'billing',
                            'billing_id' => $invoice->costlockerInvoiceId,
                        ],
                        'billing' => [
                            'description' => $description,
                            'status' => 'sent',
                        ],
                    ],
                ],
            ]
        );
    }
}
