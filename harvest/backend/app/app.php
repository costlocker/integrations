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
$getLogFile = function ($file) {
    $env = getenv('APP_ENV');
    return __DIR__ . "/../var/log/{$env}-{$file}.log";
};
$app->register(new Silex\Provider\MonologServiceProvider(), [
    'monolog.logfile' => $getLogFile('app'),
    'monolog.level' => Monolog\Logger::NOTICE,
]);
$app['monolog.import'] = function () use ($getLogFile) {
    $handler = new \Monolog\Handler\StreamHandler($getLogFile('import'));
    return new \Monolog\Logger('import', [$handler]);
};
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

$app['client.costlocker'] = function ($app) {
    return new \Costlocker\Integrations\CostlockerClient($app['guzzle'], $app['client.user'], getenv('CL_HOST'));
};

$app['client.user'] = function ($app) {
    return new Costlocker\Integrations\Auth\GetUser($app['session']);
};

$app['client.check'] = function ($app) {
    return new Costlocker\Integrations\Auth\CheckAuthorization($app['session'], $app['client.costlocker']);
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
        $app['client.check']->verifyCostlockerToken();
        return $app['client.user']();
    });

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
        $apiClient = new Costlocker\Integrations\HarvestClient(
            $app['guzzle.cached'],
            $app['client.user']->getHarvestUrl(),
            $app['client.user']->getHarvestAuthorization()
        );
        $strategy = new Costlocker\Integrations\Harvest\GetDataFromHarvest($app['import.database']);
        $data = $strategy($r, $apiClient);
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('harvest'));

return $app;
