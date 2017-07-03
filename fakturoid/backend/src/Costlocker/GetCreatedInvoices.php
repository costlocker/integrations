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
            : $this->database->findLatestInvoices($this->getUser->getFakturoidAccount(), 20);
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
                        'link' => getenv('CL_HOST') . "/projects/detail/{$i->data['request']['costlocker']['project']['id']}/billing",
                    ],
                    'fakturoid' => [
                        'user' => $i->fakturoidUser->data['full_name'],
                        'subject' => $i->data['request']['fakturoid']['subject'],
                        'lines' => array_values($i->data['request']['fakturoid']['lines']),
                        'number' => $i->fakturoidInvoiceNumber,
                        'link' => $i->data['response']['html_url'],
                        'amount' => $i->data['response']['total'],
                    ],
                ];
            },
            $invoices
        );
    }
}
