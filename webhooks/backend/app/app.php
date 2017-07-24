<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$app = new Silex\Application();
$app['debug'] = in_array(getenv('APP_ENV'), ['local', 'test']);

$app->register(new \Costlocker\Integrations\Api\LogErrorsAndExceptions(__DIR__ . '/../var/log'));
$app->before(new \Costlocker\Integrations\Api\DecodeJsonRequest());
$app->error(new \Costlocker\Integrations\Api\ConvertExceptionToJson());

$app['guzzle'] = function () {
    return new \GuzzleHttp\Client();
};

$app
    ->get('/', function (Request $r) {
        $json = new JsonResponse([
            'proxy url' => "POST {$r->getUriForPath('/')}",
            'proxy request template' => [
                'method' => 'GET',
                'isDebug' => false,
                'url' => 'https://new.costlocker.com/api-public/v2/webhooks',
                'headers' => ['Authorization' => '...'],
                'body' => '',
            ],
        ]);
        $json->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $json;
    });

$app
    ->post('/', function (Request $r) use ($app) {
        $proxy = new \Costlocker\Integrations\ProxyClient(
            $app['guzzle'],
            array_filter(explode(',', getenv('ALLOWED_HOSTS')))
        );
        return $proxy($r);
    });

$app
    ->post('/log', function (Request $r) use ($app) {
        $app['logger']->error(
            "Frontend error '{$r->request->get('error')}'",
            ['stack' => explode("\n", $r->request->get('stack')), 'user' => $r->request->get('user')]
        );
        return new JsonResponse(200);
    });

return $app;
