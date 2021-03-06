<?php

namespace Costlocker\Integrations\Costlocker;

use Mockery as m;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Entities\Invoice;
use GuzzleHttp\Psr7\Response;

class MarkSentInvoiceTest extends \PHPUnit_Framework_TestCase
{
    const FAKTUROID_NUMBER = 'FA0001';

    private $costlockerRequest;

    /** @dataProvider provideDescription */
    public function testUpdateDescriptionInCostlocker($description, $expectedDescription)
    {
        $invoice = $this->givenBilling(['costlockerDescription' => $description]);
        $this->markSentInvoice($invoice);
        assertThat($this->costlockerRequest, containsString($expectedDescription));
    }

    public function provideDescription()
    {
        return [
            'set fakturoid number when description is empty' => [null, self::FAKTUROID_NUMBER],
            'dont override existing description' => ['my description', 'my description'],
        ];
    }

    public function testOverrideBillingDateByIssuedDate()
    {
        $invoice = $this->givenBilling(['faktuoroidIssuedDate' => '2017-07-01']);
        $this->markSentInvoice($invoice);
        assertThat($this->costlockerRequest, containsString('"date":"2017-07-01"'));
    }

    public function testCreateNewBilling()
    {
        $invoice = $this->givenBilling(['costlockerId' => null]);
        $this->markSentInvoice($invoice);
        assertThat($this->costlockerRequest, containsString('"total_amount"'));
        assertThat($this->costlockerRequest, not(containsString('"billing_id"')));
    }

    private function givenBilling(array $config)
    {
        $config += [
            'costlockerId' => 123,
            'costlockerDescription' => '',
            'faktuoroidIssuedDate' => date('Y-m-d'),
        ];
        $invoice = new Invoice();
        $invoice->costlockerInvoiceId = $config['costlockerId'];
        $invoice->data = json_decode(file_get_contents(__DIR__ . '/fixtures/invoice-data.json'), true);
        $invoice->data['request']['costlocker']['billing']['billing']['description']
            = $config['costlockerDescription'];
        $invoice->data['response']['issued_on']
            = $config['faktuoroidIssuedDate'];
        $invoice->fakturoidInvoiceNumber = self::FAKTUROID_NUMBER;
        return $invoice;
    }

    private function markSentInvoice(Invoice $invoice)
    {
        $this->costlockerRequest = null;
        $client = m::mock(CostlockerClient::class);
        $client->shouldReceive('__invoke')->once()->with(
            '/projects',
            m::on(function () {
                $this->costlockerRequest = json_encode(func_get_args());
                return true;
            })
        )->andReturn(new Response());
        $uc = new MarkSentInvoice($client);
        $uc($invoice);
    }
}
