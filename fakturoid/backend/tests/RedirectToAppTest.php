<?php

namespace Costlocker\Integrations\Auth;

class RedirectToAppTest extends \Costlocker\Integrations\GivenApi
{
    private $billingSpecification = '?invoice=1&project=1';

    public function testRememberCostlockerInvoiceAfterLogin()
    {
        $this->whenInvoiceIsSentInRequest();
        $this->assertThatUserIsRedirectedTo("/invoice{$this->billingSpecification}");
    }

    public function testUseInvoiceForRedirectsOnlyOnce()
    {
        $this->whenInvoiceIsSentInRequest();
        $this->assertThatUserIsRedirectedTo("/invoice{$this->billingSpecification}");
        $this->assertThatUserIsRedirectedTo('/invoice');
    }

    public function testIgnoreInvoiceIfUserIsAlreadyLoggedInFakturoid()
    {
        $this->givenUserLoggedInFakturoid();
        $this->whenInvoiceIsSentInRequest();
        $this->assertThatUserIsRedirectedTo('/invoice');
    }

    /** @dataProvider provideQueryStringWithoutInvoice */
    public function testRedirectToDefaultPageIf($queryString)
    {
        $this->request([
            'method' => 'GET',
            'url' => "/user?{$queryString}",
        ]);
        $this->assertThatUserIsRedirectedTo('/invoice');
    }

    public function provideQueryStringWithoutInvoice()
    {
        return [
            'no query string' => [''],
            'another query parameters' => ['loginError=Internal server error']
        ];
    }

    private function whenInvoiceIsSentInRequest()
    {
        $this->request([
            'method' => 'GET',
            'url' => "/user{$this->billingSpecification}",
        ]);
    }

    private function assertThatUserIsRedirectedTo($expectedUrl)
    {
        $this->urls = $this->app['redirectUrls'];
        assertThat($this->urls->goToInvoice()->getTargetUrl(), endsWith($expectedUrl));
    }
}
