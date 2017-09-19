<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

\Costlocker\Integrations\Api\CORS::enable(getenv('APP_CORS'));

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

$app['database'] = function ($app) {
    return new \Costlocker\Integrations\Database\ProjectsDatabase(
        $app['orm.em'],
        $app['client.user']
    );
};

$app['database.events'] = function ($app) {
    return new \Costlocker\Integrations\Events\EventsRepository(
        $app['orm.em'],
        $app['client.user'],
        new Costlocker\Integrations\Events\EventsToJson(
            $app['database'],
            $app['client.basecamp']
        )
    );
};

$app['events.logger'] = function ($app) {
    return new \Costlocker\Integrations\Events\EventsLogger(
        $app['orm.em'],
        $app['client.user']
    );
};

$app['events.pushSyncRequest'] = function ($app) {
    return new \Costlocker\Integrations\Sync\Queue\PushSyncRequest($app['events.logger']);
};

$app['guzzle'] = function () {
    return new \GuzzleHttp\Client();
};

$app['client.costlocker'] = function ($app) {
    return new \Costlocker\Integrations\CostlockerClient($app['guzzle'], $app['client.user'], getenv('CL_HOST'));
};

$app['client.basecamp'] = function ($app) {
    return new \Costlocker\Integrations\Basecamp\BasecampAdapter($app['client.user']);
};

$app['signature.costlocker'] = function () {
    return new \Costlocker\Integrations\Sync\CostlockerWebhookVerifier(getenv('CL_CLIENT_SECRET'));
};

$app['oauth.basecamp'] = function () {
    return new Costlocker\Integrations\Auth\Provider\BasecampOAuthProvider([
        'clientId' => getenv('BASECAMP_CLIENT_ID'),
        'clientSecret' => getenv('BASECAMP_CLIENT_SECRET'),
        'redirectUri' => getenv('BASECAMP_REDIRECT_URL'),
    ]);
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
    return new Costlocker\Integrations\Auth\GetUser($app['session'], $app['orm.em']);
};

$app['client.check'] = function ($app) {
    return new Costlocker\Integrations\Auth\CheckAuthorization(
        $app['session'],
        $app['client.costlocker'],
        $app['client.basecamp']
    );
};

$app['logout'] = function ($app) {
    return new Costlocker\Integrations\Auth\LogoutUser($app['session'], getenv('APP_FRONTED_URL'));
};

$checkAuthorization = function ($service) use ($app) {
    // prevents 'Cannot override frozen service "guzzle"'
    return function () use ($service, $app) {
        return $app['client.check']->checkAccount($service);
    };
};

$checkCsrf = function () {
    return function (Request $r, $app) {
        return $app['client.check']->checkCsrfToken($r->headers->get('X-CSRF-TOKEN'));
    };
};

$getWebhookUrl = function (Request $r) {
    return getenv('APP_WEBHOOK_DEV_URL') ?: $r->getUriForPath('/webhooks/handler');
};

$app
    ->get('/', function (Request $r) use ($getWebhookUrl) {
        $json = new JsonResponse([
            'webhookUrl' => "POST {$getWebhookUrl($r)}",
        ]);
        $json->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        return $json;
    });

$app
    ->post('/log', function (Request $r) use ($app) {
        $app['logger']->error(
            "Frontend error '{$r->request->get('error')}'",
            ['stack' => explode("\n", $r->request->get('stack')), 'user' => $r->request->get('user')]
        );
        return new JsonResponse(200);
    });

$app
    ->get('/user', function () use ($app) {
        $isAddonDisabled = $app['client.check']->verifyTokens();
        // $app['client.user'] still sees removed costlocker userId
        $getUser = new Costlocker\Integrations\Auth\GetUser($app['session'], $app['orm.em']);
        return $getUser($isAddonDisabled);
    });

$app
    ->get('/oauth/costlocker', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Auth\AuthorizeInCostlocker(
            $app['session'],
            $app['oauth.costlocker'],
            new Costlocker\Integrations\Database\PersistCostlockerUser($app['orm.em']),
            $app['logger'],
            $app['logout'],
            getenv('APP_FRONTED_URL')
        );
        return $strategy($r);
    });

$app
    ->get('/oauth/basecamp', function (Request $r) use ($app) {
        $allowedProducts = [
            Costlocker\Integrations\Basecamp\Api\Connect::BASECAMP_CLASSIC_TYPE,
            Costlocker\Integrations\Basecamp\Api\Connect::BASECAMP_BCX_TYPE,
            Costlocker\Integrations\Basecamp\Api\Connect::BASECAMP_V3_TYPE,
        ];
        $strategy = new Costlocker\Integrations\Auth\AuthorizeInBasecamp(
            $app['session'],
            $app['oauth.basecamp'],
            new Costlocker\Integrations\Database\PersistBasecampUser(
                $app['orm.em'],
                $app['client.user'],
                $allowedProducts
            ),
            $app['logger'],
            getenv('APP_FRONTED_URL')
        );
        return $strategy($r);
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->post('/logout', function () use ($app) {
        return $app['logout']();
    });

$app
    ->get('/costlocker', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Costlocker\GetProjects(
            $app['client.costlocker'],
            $app['client.basecamp'],
            $app['database']
        );
        $data = $strategy($r);
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->get('/events', function (Request $r) use ($app) {
        $project = $r->query->get('project');
        return new JsonResponse($app['database.events']->findLatestEvents($project));
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->post('/events/undo', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Events\UndoEvent(
            $app['database.events'],
            $app['database'],
            $app['events.logger']
        );
        return $strategy($r->query->get('id'));
    })
    ->before($checkAuthorization('costlocker'))
    ->before($checkCsrf());

$app
    ->post('/settings', function (Request $r) use ($app) {
        $uc = new \Costlocker\Integrations\Database\UpdateSettings($app['orm.em'], $app['client.user']);
        $uc($r->request->all());
        return new JsonResponse();
    })
    ->before($checkAuthorization('costlocker'))
    ->before($checkCsrf());

$app
    ->post('/disconnect', function (Request $r) use ($app) {
        $wasDisconnected = false;
        if ($r->request->get('user')) {
            $uc = new Costlocker\Integrations\Database\DisconnectBasecampAccount(
                $app['orm.em'],
                $app['client.user'],
                $app['events.logger']
            );
            $wasDisconnected = $uc($r->request->get('user'));
        } elseif ($r->request->get('project')) {
            $uc = new Costlocker\Integrations\Database\DisconnectProject(
                $app['db'],
                $app['client.user'],
                $app['events.logger']
            );
            $wasDisconnected = $uc($r->request->get('project'));
        }
        return new JsonResponse([], $wasDisconnected ? 200 : 400);
    })
    ->before($checkAuthorization('basecamp'))
    ->before($checkCsrf());

$app
    ->get('/basecamp', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Basecamp\GetProjects($app['client.basecamp'], $app['client.user']);
        $data = $strategy($r);
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('costlocker'));

$pushEvent = function ($event, array $data, Request $r) use ($app, $getWebhookUrl) {
    $app['events.pushSyncRequest']($event, $data, $getWebhookUrl($r));
    return new JsonResponse([], 200);
};
    
$app
    ->post('/sync', function (Request $r) use ($pushEvent) {
        $data = $r->request->all();
        return $pushEvent(\Costlocker\Integrations\Entities\Event::MANUAL_SYNC, $data, $r);
    })
    ->before($checkAuthorization('basecamp'))
    ->before($checkCsrf());

$app
    ->get('/webhooks/handler', function (Request $r) use ($getWebhookUrl) {
        $json = new JsonResponse([
            'example' => "POST {$getWebhookUrl($r)} --data '[\"some json\"]'",
            'supported_webhooks' => [
                'basecamp3' => 'https://github.com/basecamp/bc3-api/blob/master/sections/webhooks.md#webhooks',
                'costlocker' => 'http://docs.costlocker2.apiary.io/#reference/0/webhooks/get-webhook-example',
            ],
        ]);
        $json->setEncodingOptions(JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return $json;
    });

$app
    ->post('/webhooks/handler', function (Request $r) use ($pushEvent) {
        $data = [
            'rawBody' => $r->getContent(),
            'headers' => $r->headers->all()
        ];
        return $pushEvent(\Costlocker\Integrations\Entities\Event::WEBHOOK_SYNC, $data, $r);
    });

return $app;
