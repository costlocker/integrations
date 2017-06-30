<?php

namespace Costlocker\Integrations\Costlocker;

use Mockery as m;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Entities\Invoice;

class MarkSentInvoiceTest extends \PHPUnit_Framework_TestCase
{
    const FAKTUROID_NUMBER = 'FA0001';

    private $costlockerRequest;

    /** @dataProvider provideDescription */
    public function testUpdateDescriptionInCostlocker($description, $expectedDescription)
    {
        $invoice = $this->givenBillingWithDescription($description);
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
    
    private function givenBillingWithDescription($description)
    {
        $invoice = new Invoice();
        $invoice->data = json_decode(file_get_contents(__DIR__ . '/fixtures/invoice-data.json'), true);
        $invoice->data['request']['costlocker']['billing']['billing']['description'] = $description;
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
        );
        $uc = new MarkSentInvoice($client);
        $uc($invoice);
    }
}
