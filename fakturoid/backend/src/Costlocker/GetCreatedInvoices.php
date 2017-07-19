<?php

namespace Costlocker\Integrations\Costlocker;

use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Entities\Invoice;
use Costlocker\Integrations\Database\Database;
use Symfony\Component\HttpFoundation\Request;

class GetCreatedInvoices
{
    private $database;
    private $getUser;

    public function __construct(Database $db, GetUser $u)
    {
        $this->database = $db;
        $this->getUser = $u;
    }

    public function __invoke(Request $r)
    {
        $invoices = $r->query->get('project')
            ? $this->database->findProjectInvoices($r->query->get('project'))
            : $this->database->findLatestInvoices($this->getUser->getCostlockerUser(), 20);
        return array_map(
            function (Invoice $i) {
                return [
                    'id' => $i->id,
                    'date' => $i->createdAt->format('Y-m-d H:i:s'),
                    'costlocker' => [
                        'user' => $i->costlockerUser->data['person']['first_name']
                            . " {$i->costlockerUser->data['person']['last_name']}",
                        'project' => $i->data['request']['costlocker']['project'],
                        'billing' => $i->data['request']['costlocker']['billing'],
                        'link' =>
                            getenv('CL_HOST') .
                            "/projects/detail/{$i->data['request']['costlocker']['project']['id']}/billing",
                        'update' => $i->getUpdateStatus(),
                    ],
                    'fakturoid' => [
                        'user' => $i->fakturoidUser->data['full_name'],
                        'subject' => $i->data['request']['fakturoid']['subject'],
                        'type' => $i->data['request']['fakturoid']['type'] ?? 'invoice',
                        'note' => $i->data['request']['fakturoid']['note'] ?? '',
                        'lines' => array_values($i->data['request']['fakturoid']['lines']),
                        'number' => $i->fakturoidInvoiceNumber,
                        'link' => $i->data['response']['html_url'],
                        'amount' => $i->data['response']['total'],
                        'template' => [
                            'actions' => $this->guessActions(array_keys($i->data['request']['fakturoid']['lines'])),
                        ],
                    ],
                ];
            },
            $invoices
        );
    }

    private function guessActions(array $lineKeys)
    {
        $availableActions = [
            'expense' => 'expenses',
            'activity' => 'activities',
            'people' => 'people',
            'discount' => 'discounts',
            'default' => 'custom',
            'empty' => 'custom',
        ];
        $actions = [];
        foreach ($lineKeys as $key) {
            foreach ($availableActions as $action => $status) {
                if (is_int(strpos($key, $action))) {
                    $actions[] = $status;
                }
            }
        }
        return $actions ? array_values(array_unique($actions)) : ['custom'];
    }
}
