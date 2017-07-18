<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\Entities\Invoice;
use Costlocker\Integrations\Database\Database;

class GetInvoice
{
    const STATUS_UNKNOWN = 'UNKNOWN';
    const STATUS_NOT_DRAFT = 'NOT_DRAFT';
    const STATUS_ALREADY_IMPORTED = 'ALREADY_IMPORTED';
    const STATUS_CAN_BE_IMPORTED = 'READY';
    const STATUS_NEW = 'NEW';

    private $client;
    private $database;

    public function __construct(CostlockerClient $c, Database $db)
    {
        $this->client = $c;
        $this->database = $db;
    }

    public function __invoke(Request $r)
    {
        $response = $this->client->__invoke(
            "/projects/{$r->query->get('project')}?types=billing,expenses,peoplecosts,discounts"
        );

        if ($response->getStatusCode() != 200) {
            return [
                'status' => self::STATUS_UNKNOWN,
                'costlocker' => null,
                'fakturoid' => null,
            ];
        }

        $billingId = $r->query->get('billing');
        $json = json_decode($response->getBody(), true)['data'];
        $items = $this->separateItems($json['items']);
        $billing = $this->findDraftBilling($items['billing'], $billingId, $r->query->get('amount'));
        $invoice = $this->database->findInvoice($billingId);
        return [
            'status' => $this->billingToStatus($billing, $invoice),
            'costlocker' => [
                'project' => [
                    'id' => $json['id'],
                    'name' => $json['name'],
                    'client' => $json['client'],
                    'project_id' => $json['project_id'],
                    'budget' => [
                        'expenses' => $items['expense'],
                        'peoplecosts' => $items['peoplecosts'],
                        'discount' => $items['discount'],
                    ],
                ],
                'billing' => $billing,
            ],
            'fakturoid' => $this->invoiceToJson($invoice) + [
                'template' => [
                    'subject' => $this->database->findLatestSubjectForClient($json['client']['id']),
                ],
            ],
        ];
    }

    private function separateItems(array $items)
    {
        $results = [
            'peoplecosts' => [],
            'billing' => [],
            'expense' => [],
            'discount' => 0,
        ];
        foreach ($items as $item) {
            $type = $item['item']['type'];
            if ($type == 'activity') {
                $results['peoplecosts'][$item['item']['activity_id']] = $item + ['people' => []];
            } elseif ($type == 'person') {
                $results['peoplecosts'][$item['item']['activity_id']]['people'][] = $item;
            } elseif (in_array($type, ['billing', 'expense'])) {
                $results[$type][] = $item;
            } elseif ($type == 'discount') {
                $results[$type] = $item['discount']['total_amount'];
            }
        }
        $results['peoplecosts'] = array_values($results['peoplecosts']);
        return $results;
    }

    private function findDraftBilling(array $items, $invoiceId, $amount)
    {
        foreach ($items as $item) {
            if ($item['item']['billing_id'] == $invoiceId) {
                return $item;
            }
        }
        if ($invoiceId == self::STATUS_NEW && $amount > 0) {
            return [
                'item' => [
                    'type' => 'billing',
                    'billing_id' => null,
                ],
                'billing' => [
                    'description' => '',
                    'total_amount' => (float) $amount,
                    'date' => date('Y-m-d'),
                    'status' => 'draft',
                ],
            ];
        }
        return null;
    }

    private function billingToStatus(array $billing = null, Invoice $i = null)
    {
        if (!$billing) {
            return self::STATUS_UNKNOWN;
        }
        if ($i) {
            return self::STATUS_ALREADY_IMPORTED;
        }
        if (!$billing['item']['billing_id']) {
            return self::STATUS_NEW;
        }
        return $billing['billing']['status'] == 'draft' ? self::STATUS_CAN_BE_IMPORTED : self::STATUS_NOT_DRAFT;
    }

    private function invoiceToJson(Invoice $i = null)
    {
        if (!$i) {
            return [];
        }
        return [
            'number' => $i->fakturoidInvoiceNumber,
            'link' => $i->data['response']['html_url'],
        ];
    }
}
