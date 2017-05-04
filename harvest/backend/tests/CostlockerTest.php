<?php

namespace Costlocker\Integrations;

use Mockery as m;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class CostlockerTest extends GivenApi
{
    private $client;
    private $requests = [];
    private $createdId = 2367; // id in costlocker.json

    public function createApplication()
    {
        $app = parent::createApplication();
        $this->client = m::mock(Client::class);
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

    public function testTransformHarvestDataToCostlockerFormat()
    {
        $this->spyApiCalls();
        $this->importProject();
        assertThat($this->requests['projects']['name'], is(nonEmptyString()));
        assertThat($this->requests['projects']['client'], is(nonEmptyString()));
        assertThat($this->requests['projects']['responsible_people'], is(arrayWithSize(1)));
        assertThat($this->requests['projects']['items'], is(arrayWithSize(6)));
        assertThat($this->requests['timeentries'], is(arrayWithSize(2)));
    }

    public function testReturnUrlToCreatedProjectInCostlocker()
    {
        $this->spyApiCalls();
        $response = $this->importProject();
        $json = json_decode($response->getContent(), true);
        assertThat($json['projectUrl'], containsString('/projects/'));
        assertThat($json['projectUrl'], containsString("/{$this->createdId}/"));
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

    private function spyApiCalls()
    {
        $this->givenLoggedUser();
        $this->client->shouldReceive('post')
            ->andReturnUsing(function ($url, array $data) {
                $versionPosition = strpos($url, 'v2');
                $path = trim(substr($url, $versionPosition + 2), '/');
                $this->requests[$path] = $data['json'];
                return new Response(200, [], json_encode($this->givenJsonResponse('costlocker.json')));
            });
        $this->importProject();
    }

    private function givenLoggedUser()
    {
        $this->app['session']->set('costlocker', [
            'account' => [
                'person' => [
                    'email' => 'irrelevant email',
                ],
            ],
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
            'json' => $this->givenJsonResponse('harvest.json'),
        ]);
    }

    private function givenJsonResponse($file)
    {
        return json_decode(file_get_contents(__DIR__ . "/fixtures/{$file}"));
    }

    public function tearDown()
    {
        m::close();
    }
}
