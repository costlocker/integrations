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
$monologConfig = [
    'monolog.logfile' => __DIR__ . '/../var/log/app.log',
    'monolog.level' => Monolog\Logger::NOTICE,
];
$app->register(new Silex\Provider\MonologServiceProvider(), $monologConfig);

$app->register(new Silex\Provider\SessionServiceProvider(), [
    'session.test' => getenv('APP_ENV') === 'test',
    'session.storage.save_path' => __DIR__ . '/../var/sessions/'
]);

$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

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
    return $app['guzzle'];
};

$getLoggedUser = new Costlocker\Integrations\Auth\GetUser($app['session']);

$app
    ->get('/user', function () use ($getLoggedUser) {
        return $getLoggedUser();
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
            $app['guzzle'],
            $app['session'],
            new \Monolog\Logger(
                'import',
                [new \Monolog\Handler\StreamHandler(__DIR__ . '/../var/log/import.log')]
            ),
            getenv('CL_HOST')
        );
        return $strategy($r);
    })->before(\Costlocker\Integrations\Auth\CheckAuthorization::costlocker($app['session']));

$app
    ->post('/harvest', function (Request $r) use ($app, $getLoggedUser) {
        $strategy = new \Costlocker\Integrations\Auth\AuthorizeInHarvest($app['guzzle'], $app['session'], $getLoggedUser);
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

$app->error(function (Exception $e) {
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
    }
    return ResponseHelper::error('Internal Server Error', 500);
});
 
return $app;
