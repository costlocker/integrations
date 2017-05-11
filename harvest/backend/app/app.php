<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Api\ResponseHelper;

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$app = new Silex\Application();
$app['debug'] = getenv('APP_ENV') !== 'production';

$app->register(new Silex\Provider\SessionServiceProvider(), [
    'session.test' => getenv('APP_ENV') === 'test',
    'session.storage.save_path' => __DIR__ . '/../var/sessions/'
]);
$app->register(new \Costlocker\Integrations\Api\LogErrorsAndExceptions(__DIR__ . '/../var/log'));
$app->before(new \Costlocker\Integrations\Api\DecodeJsonRequest());
$app->error(new \Costlocker\Integrations\Api\ConvertExceptionToJson());

$app['guzzle'] = function () {
    return new \GuzzleHttp\Client();
};

$app['guzzle.cached'] = function ($app) {
    // cache response only in local environment
    if ($app['debug']) {
        $ttl = 24 * 60 * 60;
        $cache = new \Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy(
            new \Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage(
                new \Doctrine\Common\Cache\FilesystemCache(__DIR__ . '/../var/cache')
            ),
            $ttl
        );
        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push(new \Kevinrob\GuzzleCache\CacheMiddleware($cache), 'cache');
        return new \GuzzleHttp\Client(['handler' => $stack]);
    }
    return new \GuzzleHttp\Client();
};

$app['client.costlocker'] = function ($app) {
    return new \Costlocker\Integrations\CostlockerClient($app['guzzle'], $app['client.user'], getenv('CL_HOST'));
};

$app['client.harvest'] = function ($app) {
    return new Costlocker\Integrations\HarvestClient($app['guzzle.cached'], $app['client.user']);
};

$app['client.user'] = function ($app) {
    return new Costlocker\Integrations\Auth\GetUser($app['session']);
};

$app['client.check'] = function ($app) {
    return new Costlocker\Integrations\Auth\CheckAuthorization(
        $app['session'],
        $app['client.costlocker'],
        $app['client.harvest']
    );
};

$checkAuthorization = function ($service) use ($app) {
    // prevents 'Cannot override frozen service "guzzle"'
    return function () use ($service, $app) {
        return $app['client.check']->checkAccount($service);
    };
};

$app['import.database'] = function ($app) {
    return new Costlocker\Integrations\Sync\ImportDatabase($app['client.user'], __DIR__ . '/../var/database');
};

$app
    ->get('/user', function () use ($app) {
        $app['client.check']->verifyTokens();
        return $app['client.user']();
    })
    ->after(new Costlocker\Integrations\Auth\FakeCostlockerAuthorization($app['session']));

$app
    ->get('/oauth/costlocker', function (Request $r) use ($app) {
        $strategy = Costlocker\Integrations\Auth\AuthorizeInCostlocker::buildFromEnv($app['session']);
        return $strategy($r);
    });

$app
    ->get('/oauth/harvest', function (Request $r) use ($app) {
        $strategy = Costlocker\Integrations\Auth\AuthorizeInHarvest::buildFromEnv($app['session']);
        return $strategy($r);
    });

$app
    ->post('/costlocker', function (Request $r) use ($app) {
        if (getenv('APP_IMPORT_DISABLED') == 'true') {
            return ResponseHelper::error('Import is disabled');
        }
        $strategy = new \Costlocker\Integrations\Sync\HarvestToCostlocker(
            $app['client.costlocker'],
            $app['import.database'],
            $app['monolog.import']
        );
        return $strategy($r);
    })
    ->before($checkAuthorization('harvest'))
    ->before($checkAuthorization('costlocker'));

$app
    ->get('/harvest', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Harvest\GetDataFromHarvest($app['import.database']);
        $data = $strategy($r, $app['client.harvest']);
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('harvest'));

return $app;
