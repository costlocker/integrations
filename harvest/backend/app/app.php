<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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

$getLoggedUser = new Costlocker\Integrations\Auth\GetUser($app['session']);

$app
    ->get('/user', function () use ($getLoggedUser) {
        return $getLoggedUser();
    });

$app
    ->get('/costlocker/login', function (Request $r) use ($app) {
        $strategy = Costlocker\Integrations\Auth\AuthorizeInCostlocker::buildFromEnv($app['session']);
        return $strategy($r);
    });

$app
    ->post('/harvest', function (Request $r) use ($app, $getLoggedUser) {
        $strategy = new \Costlocker\Integrations\Auth\AuthorizeInHarvest($app['session'], $getLoggedUser);
        return $strategy($r);
    });

$app
    ->get('/harvest', function (Request $r) use ($app) {
        $harvest = $app['session']->get('harvest');
        $apiClient = new Costlocker\Integrations\HarvestClient($harvest['account']['company_url'], $harvest['auth']);
        $strategy = new Costlocker\Integrations\Harvest\GetDataFromHarvest();
        $data = $strategy($r, $apiClient);
        return new JsonResponse($data);
    })->before(\Costlocker\Integrations\Auth\CheckAuthorization::harvest($app['session']));

$app->error(function (Exception $e) {
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return new JsonResponse(['errors' => [$e->getMessage()]], $e->getStatusCode());
    }
    return new JsonResponse(null, 500);
});
 
return $app;
