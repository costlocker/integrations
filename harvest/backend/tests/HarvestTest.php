<?php

namespace Costlocker\Integrations;

class HarvestTest extends GivenApi
{
    public function testUnauthorizedRequest()
    {
        $response = $this->request([
            'method' => 'GET',
            'url' => '/harvest',
        ]);
        assertThat($response->getStatusCode(), is(401));
    }
}
