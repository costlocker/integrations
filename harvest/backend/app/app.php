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

$app
    ->get('/harvest', function (){
        return new JsonResponse([
            'data' => [],
        ]);
    });

$app
    ->post('/harvest', function (Request $r) {
        $client = new GuzzleHttp\Client();
        $response = $client->get("https://{$r->request->get('domain', 'a')}.harvestapp.com/account/who_am_i", [
            'http_errors' => false,
            'auth' => [$r->request->get('username'), $r->request->get('password')],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
        $account = json_decode($response->getBody(), true);
        if ($response->getStatusCode() != 200) {
            return new JsonResponse([], $response->getStatusCode());
        }
        return new JsonResponse([
            'company_name' => $account['company']['name'],
            'company_url' => $account['company']['base_uri'],
            'user_name' => "{$account['user']['first_name']} {$account['user']['last_name']}",
            'user_avatar' => $account['user']['avatar_url'],
        ]);
    });

$app->error(function (Exception $e) {
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return new JsonResponse(['errors' => [$e->getMessage()]], $e->getStatusCode());
    }
    return new JsonResponse(null, 500);
});
 
return $app;
