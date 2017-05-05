<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Api\ResponseHelper;

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$app = new Silex\Application();
$app['debug'] = getenv('APP_ENV') !== 'production';

\Symfony\Component\Debug\ErrorHandler::register();
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => __DIR__ . '/../var/log/app.log',
    'monolog.level' => Monolog\Logger::NOTICE,
]);
$app->register(new Silex\Provider\SessionServiceProvider(), [
    'session.test' => getenv('APP_ENV') === 'test',
    'session.storage.save_path' => __DIR__ . '/../var/sessions/'
]);

$app->before(new Costlocker\Integrations\Api\DecodeJsonRequest());
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

$app['client.costlocker'] = function () use ($app) {
    return new \Costlocker\Integrations\CostlockerClient($app['guzzle'], $app['session'], getenv('CL_HOST'));
};

$app['client.user'] = function () use ($app) {
    return new Costlocker\Integrations\Auth\GetUser($app['session'], $app['client.costlocker']);
};

$app
    ->get('/user', function () use ($app) {
        return $app['client.user'](true);
    });

$app
    ->get('/costlocker', function (Request $r) use ($app) {
        $strategy = Costlocker\Integrations\Auth\AuthorizeInCostlocker::buildFromEnv($app['session']);
        return $strategy($r);
    });

$app
    ->post('/costlocker', function (Request $r) use ($app) {
        if (getenv('CL_IMPORT_DISABLED') == 'true') {
            return ResponseHelper::error('Import is disabled');
        }
        $strategy = new \Costlocker\Integrations\HarvestToCostlocker(
            $app['client.costlocker'],
            new \Monolog\Logger(
                'import',
                [new \Monolog\Handler\StreamHandler(__DIR__ . '/../var/log/import.log')]
            )
        );
        return $strategy($r);
    })->before(\Costlocker\Integrations\Auth\CheckAuthorization::costlocker($app['session']));

$app
    ->post('/harvest', function (Request $r) use ($app) {
        $strategy = new \Costlocker\Integrations\Auth\AuthorizeInHarvest($app['guzzle'], $app['session'], $app['client.user']);
        return $strategy($r);
    });

$app
    ->get('/harvest', function (Request $r) use ($app) {
        $harvest = $app['session']->get('harvest');
        $apiClient = new Costlocker\Integrations\HarvestClient($app['guzzle.cached'], $harvest['account']['company_url'], $harvest['auth']);
        $strategy = new Costlocker\Integrations\Harvest\GetDataFromHarvest();
        $data = $strategy($r, $apiClient);
        return new JsonResponse($data);
    })->before(\Costlocker\Integrations\Auth\CheckAuthorization::harvest($app['session']));

return $app;
