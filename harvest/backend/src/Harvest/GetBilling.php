<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetBilling
{
    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $dateStart = $r->query->get('from', date('Y0101'));
        $dateEnd = $r->query->get('to', date('Ymd'));
        $client = $r->query->get('client', '');

        $stats = [
            'draft' => 0,
            'sent' => 0,
        ];
        $invoices = array_map(
            function (array $invoice) use (&$stats) {
                $isInvoiced = $invoice['invoices']['state'] == 'paid';
                $amount = $invoice['invoices']['amount'];
                if ($isInvoiced) {
                    $stats['sent'] += $amount;
                } else {
                    $stats['draft'] += $amount;
                }
                return [
                    'id' => $invoice['invoices']['id'],
                    'description' =>
                        "#{$invoice['invoices']['number']}" .
                        ($invoice['invoices']['subject'] ? " {$invoice['invoices']['subject']}" : ''),
                    'total_amount' => $amount,
                    'date' => $invoice['invoices']['issued_at'],
                    'is_invoiced' => $isInvoiced,
                ];
            },
            $apiClient("/invoices?from={$dateStart}&to={$dateEnd}&client={$client}&page=1")
        );
        return [
            'stats' => $stats,
            'invoices' => $invoices,
        ];
    }
}
