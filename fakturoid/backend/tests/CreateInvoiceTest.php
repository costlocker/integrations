<?php

namespace Costlocker\Integrations\Fakturoid;

use Mockery as m;
use Costlocker\Integrations\FakturoidClient;
use Costlocker\Integrations\Auth\GetUser;
use Costlocker\Integrations\Costlocker\MarkSentInvoice;
use Costlocker\Integrations\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use GuzzleHttp\Psr7\Response;

class CreateInvoiceTest extends \PHPUnit_Framework_TestCase
{
    private $jsonRequest;
    private $createdInvoice;

    /** @dataProvider provideVat */
    public function testAddVat(array $settings, $expectedVat)
    {
        $this->givenRequest($settings);
        $this->whenInvoiceIsCreated();
        $this->everyProductShouldHaveVat($expectedVat);
    }

    public function provideVat()
    {
        return [
            'no VAT' => [
                ['hasVat' => false, 'vat' => 21],
                0
            ],
            'has VAT' => [
                ['hasVat' => true, 'vat' => 21],
                21
            ],
        ];
    }
    
    private function givenRequest(array $settings)
    {
        $this->jsonRequest = array_replace_recursive(
            json_decode(file_get_contents(__DIR__ . '/fixtures/invoice-data.json'), true)['request'],
            ['fakturoid' => $settings]
        );
    }

    private function whenInvoiceIsCreated()
    {
        $client = m::mock(FakturoidClient::class);
        $client->shouldReceive('__invoke')->once()->andReturn(new Response(201));
        $user = m::mock(GetUser::class);
        $user->shouldReceive('getCostlockerUser')->once();
        $markSent = m::mock(MarkSentInvoice::class);
        $markSent->shouldReceive('__invoke')->once();
        $db = m::mock(Database::class);
        $db->shouldReceive('persist')->once()->with(
            m::on(function ($invoice) {
                $this->createdInvoice = $invoice;
                return true;
            })
        );

        $request = new Request();
        $request->request = new ParameterBag($this->jsonRequest);
        $uc = new CreateInvoice($client, $user, $markSent, $db);
        $uc($request);
    }

    private function everyProductShouldHaveVat($expectedVat)
    {
        foreach ($this->createdInvoice->data['request']['fakturoid']['lines'] as $line) {
            assertThat($line['vat'], identicalTo($expectedVat));
        }
    }

    public function tearDown()
    {
        m::close();
    }
}
