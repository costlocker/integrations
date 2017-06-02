<?php

namespace Costlocker\Integrations;

class GivenApi extends \Silex\WebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../app/app.php';
    }

    protected function request(array $config)
    {
        $client = $this->createClient();
        $client->request(
            $config['method'],
            $config['url'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($config['json'] ?? [])
        );
        return $client->getResponse();
    }
}
