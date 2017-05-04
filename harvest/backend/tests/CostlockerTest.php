<?php

namespace Costlocker\Integrations;

use Mockery as m;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class CostlockerTest extends GivenApi
{
    private $client;

    public function createApplication()
    {
        $app = parent::createApplication();
        $this->client = m::mock(\GuzzleHttp\Client::class);
        $app['guzzle'] = $this->client;
        return $app;
    }

    public function testCreateProjectAndTimesheet()
    {
        $this->givenLoggedUser();
        $this->client->shouldReceive('post')->twice()->andReturn(new Response(200));
        $response = $this->importProject();
        assertThat($response->getStatusCode(), is(200));
    }

    public function testFailedImport()
    {
        $this->givenLoggedUser();
        $this->client->shouldReceive('post')->andReturn(new Response(500));
        $response = $this->importProject();
        assertThat($response->getStatusCode(), is(400));
    }

    public function testUnauthorizedRequest()
    {
        $response = $this->importProject();
        assertThat($response->getStatusCode(), is(401));
    }

    private function givenLoggedUser()
    {
        $this->app['session']->set('costlocker', [
            'accessToken' => [
                'access_token' => 'irrelevant access token',
            ],
        ]);
    }

    private function importProject()
    {
        return $this->request([
            'method' => 'POST',
            'url' => '/costlocker',
            'json' => [],
        ]);
    }

    public function tearDown()
    {
        m::close();
    }
}
