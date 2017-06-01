<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$app = new Silex\Application();
$app['debug'] = in_array(getenv('APP_ENV'), ['local', 'test']);

$app->register(new Silex\Provider\SessionServiceProvider(), [
    'session.test' => getenv('APP_ENV') === 'test',
    'session.storage.save_path' => __DIR__ . '/../var/sessions/'
]);
$app->register(new \Costlocker\Integrations\Api\LogErrorsAndExceptions(__DIR__ . '/../var/log'));
$app->before(new \Costlocker\Integrations\Api\DecodeJsonRequest());
$app->error(new \Costlocker\Integrations\Api\ConvertExceptionToJson());

$app
    ->get('/user', function () {
        return new JsonResponse([
            'costlocker' => [
                'name' => 'John Doe',
            ],
        ]);
    });

return $app;
