<?php

namespace Costlocker\Integrations;

class AuthorizationTest extends GivenApi
{
    /** @dataProvider provideSecureUrl */
    public function testUnauthorizedRequest($secureUrl)
    {
        $response = $this->request([
            'method' => 'GET',
            'url' => $secureUrl,
        ]);
        assertThat($response->getStatusCode(), is(401));
    }

    public function provideSecureUrl()
    {
        return [
            ['/costlocker'],
            ['/basecamp'],
        ];
    }
}
