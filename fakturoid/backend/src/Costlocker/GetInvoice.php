<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\CostlockerClient;
use Symfony\Component\HttpFoundation\Request;

class GetInvoice
{
    private $client;

    public function __construct(CostlockerClient $c)
    {
        $this->client = $c;
    }

    public function __invoke(Request $r)
    {
        $response = $this->client->__invoke("/projects/{$r->query->get('project')}?types=billing");
        if ($response->getStatusCode() != 200) {
            return [
                'project' => null,
                'invoice' => null,
            ];
        }

        $json = json_decode($response->getBody(), true)['data'];
        return [
            'project' => [
                'id' => $json['id'],
                'name' => $json['name'],
                'client' => $json['client'],
                'project_id' => $json['project_id'],
            ],
            'invoice' => $this->findDraftInvoice($json['items'], $r->query->get('invoice')),
        ];
    }

    private function findDraftInvoice(array $items, $invoiceId)
    {
        foreach ($items as $item) {
            if ($item['item']['billing_id'] == $invoiceId && $item['billing']['status'] == 'draft') {
                return $item;
            }
        }
        return null;
    }
}
