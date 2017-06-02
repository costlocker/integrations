<?php

namespace Costlocker\Integrations;

class CostlockerTest extends GivenApi
{
    public function testUnauthorizedRequest()
    {
        $response = $this->importProject();
        assertThat($response->getStatusCode(), is(401));
    }

    private function importProject()
    {
        return $this->request([
            'method' => 'GET',
            'url' => '/costlocker',
        ]);
    }
}
