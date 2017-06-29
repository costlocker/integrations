<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$app = new Silex\Application();
$app['debug'] = in_array(getenv('APP_ENV'), ['local', 'test']);

$app->register(new Silex\Provider\SessionServiceProvider(), [
    'session.test' => getenv('APP_ENV') === 'test',
    'session.storage.save_path' => __DIR__ . '/../var/sessions/'
]);
$app->register(new \Costlocker\Integrations\Api\LogErrorsAndExceptions(__DIR__ . '/../var/log'));
$app->register(new \Costlocker\Integrations\Api\DatabaseProvider(__DIR__ . '/../'));
$app->before(new \Costlocker\Integrations\Api\DecodeJsonRequest());
$app->error(new \Costlocker\Integrations\Api\ConvertExceptionToJson());

$app['guzzle'] = function () {
    return new \GuzzleHttp\Client();
};

$app['client.costlocker'] = function ($app) {
    return new \Costlocker\Integrations\CostlockerClient($app['guzzle'], $app['client.user'], getenv('CL_HOST'));
};

$app['oauth.costlocker'] = function () {
    $costlockerHost = getenv('CL_HOST');
    return new \League\OAuth2\Client\Provider\GenericProvider([
        'clientId' => getenv('CL_CLIENT_ID'),
        'clientSecret' => getenv('CL_CLIENT_SECRET'),
        'redirectUri' => null,
        'urlAuthorize' => "{$costlockerHost}/api-public/oauth2/authorize",
        'urlAccessToken' => "{$costlockerHost}/api-public/oauth2/access_token",
        'urlResourceOwnerDetails' => "{$costlockerHost}/api-public/v2/me",
    ]);
};

$app['client.user'] = function ($app) {
    return new Costlocker\Integrations\Auth\GetUser($app['session']);
};

$app['client.check'] = function ($app) {
    return new Costlocker\Integrations\Auth\CheckAuthorization(
        $app['session'],
        $app['client.costlocker']
    );
};

$app
    ->get('/', function (Request $r) {
        return new JsonResponse([]);
    });

$app
    ->get('/user', function () use ($app) {
        $app['client.check']->verifyTokens();
        return $app['client.user']();
    });

$app
    ->get('/oauth/costlocker', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Auth\AuthorizeInCostlocker(
            $app['session'],
            $app['oauth.costlocker'],
            new Costlocker\Integrations\Database\PersistCostlockerUser($app['orm.em']),
            $app['logger'],
            getenv('APP_FRONTED_URL')
        );
        return $strategy($r);
    });

return $app;
