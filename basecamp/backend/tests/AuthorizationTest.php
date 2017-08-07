<?php

namespace Costlocker\Integrations;

class AuthorizationTest extends GivenApi
{
    /** @dataProvider provideSecureUrl */
    public function testUnauthorizedRequest($method, $secureUrl)
    {
        $response = $this->request([
            'method' => $method,
            'url' => $secureUrl,
        ]);
        assertThat($response->getStatusCode(), is(401));
    }

    public function provideSecureUrl()
    {
        return [
            ['GET', '/costlocker'],
            ['GET', '/basecamp?account=123'],
            ['POST', '/sync'],
        ];
    }

    public function testNotSupportedMethod()
    {
        $response = $this->request([
            'method' => 'GET',
            'url' => '/settings',
        ]);
        assertThat($response->getStatusCode(), is(405));
    }
}
