<?php

namespace Costlocker\Integrations;

class HarvestTest extends \Silex\WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../app/app.php';
    }

    public function testSuccessfulLogin()
    {
        $response = $this->request([
            'method' => 'POST',
            'url' => '/harvest',
            'json' => [
                'domain' => getenv('HARVEST_DOMAIN'),
                'username' => getenv('HARVEST_USER'),
                'password' => getenv('HARVEST_PASSWORD'),
            ],
        ]);
        assertThat($response->getStatusCode(), is(200));
    }

    public function testFailedLogin()
    {
        $response = $this->request([
            'method' => 'POST',
            'url' => '/harvest',
            'json' => [
                'domain' => getenv('HARVEST_DOMAIN'),
                'username' => 'invalid credentials'
            ],
        ]);
        assertThat($response->getStatusCode(), is(401));
    }

    private function request(array $config)
    {
        $client = $this->createClient();
        $client->request(
            $config['method'],
            $config['url'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($config['json'])
        );
        return $client->getResponse();
    }
}
