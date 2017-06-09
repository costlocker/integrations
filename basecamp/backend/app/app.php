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

$app['database'] = function ($app) {
    return new \Costlocker\Integrations\Database\ProjectsDatabase(
        $app['orm.em'],
        $app['client.user']
    );
};

$app['database.events'] = function ($app) {
    return new \Costlocker\Integrations\Events\EventsRepository(
        $app['orm.em'],
        $app['client.user']
    );
};

$app['events.logger'] = function ($app) {
    return new \Costlocker\Integrations\Events\EventsLogger(
        $app['orm.em'],
        $app['client.user']
    );
};

$app['guzzle'] = function () {
    return new \GuzzleHttp\Client();
};

$app['client.costlocker'] = function ($app) {
    return new \Costlocker\Integrations\CostlockerClient($app['guzzle'], $app['client.user'], getenv('CL_HOST'));
};

$app['client.basecamp'] = function ($app) {
    return new \Costlocker\Integrations\Basecamp\BasecampFactory($app['client.user']);
};

$app['oauth.basecamp'] = function () {
    return new \FourteenFour\BasecampAuth\Provider\Basecamp([
        'clientId' => getenv('BASECAMP_CLIENT_ID'),
        'clientSecret' => getenv('BASECAMP_CLIENT_SECRET'),
        'redirectUri' => getenv('BASECAMP_REDIRECT_URL'),
    ]);
};

$app['client.user'] = function ($app) {
    return new Costlocker\Integrations\Auth\GetUser($app['session'], $app['orm.em']);
};

$app['client.check'] = function ($app) {
    return new Costlocker\Integrations\Auth\CheckAuthorization(
        $app['session'],
        $app['client.costlocker']
    );
};

$checkAuthorization = function ($service) use ($app) {
    // prevents 'Cannot override frozen service "guzzle"'
    return function () use ($service, $app) {
        return $app['client.check']->checkAccount($service);
    };
};

$app
    ->get('/user', function () use ($app) {
        $app['client.check']->verifyTokens();
        return $app['client.user']();
    });

$app
    ->get('/oauth/costlocker', function (Request $r) use ($app) {
        $strategy = Costlocker\Integrations\Auth\AuthorizeInCostlocker::buildFromEnv(
            $app['session'],
            new Costlocker\Integrations\Database\PersistCostlockerUser($app['orm.em']),
            $app['logger']
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
            new Costlocker\Integrations\Database\PersistBasecampUser($app['orm.em'], $app['client.user'], $allowedProducts),
            $app['logger'],
            getenv('APP_FRONTED_URL')
        );
        return $strategy($r);
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->get('/costlocker', function () use ($app) {
        $strategy = new Costlocker\Integrations\Costlocker\GetProjects(
            $app['client.costlocker'],
            $app['client.basecamp'],
            $app['database']
        );
        $data = $strategy();
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->get('/events', function () use ($app) {
        return new JsonResponse($app['database.events']->findLatestEvents());
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->post('/settings', function (Request $r) use ($app) {
        $uc = new \Costlocker\Integrations\Database\UpdateSettings(
            $app['orm.em'],
            $app['client.user'],
            new \Costlocker\Integrations\Costlocker\RegisterWebhook(
                $app['client.costlocker'],
                $app['events.logger'],
                "{$r->getSchemeAndHttpHost()}/api/webhooks/handler"
            )
        );
        $uc($r->request->all());
        return new JsonResponse();
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->post('/disconnect', function (Request $r) use ($app) {
        $wasDisconnected = false;
        if ($r->request->get('user')) {
            $uc = new Costlocker\Integrations\Usecase\DisconnectBasecampAccount(
                $app['orm.em'],
                $app['client.user'],
                $app['events.logger']
            );
            $wasDisconnected = $uc($r->request->get('user'));
        } elseif ($r->request->get('project')) {
            $uc = new Costlocker\Integrations\Usecase\DisconnectProject(
                $app['db'],
                $app['client.user'],
                $app['events.logger']
            );
            $wasDisconnected = $uc($r->request->get('project'));
        }
        return new JsonResponse([], $wasDisconnected ? 200 : 400);
    })
    ->before($checkAuthorization('basecamp'));

$app
    ->get('/basecamp', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Basecamp\GetProjects($app['client.basecamp'], $app['client.user']);
        $data = $strategy($r);
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('basecamp'));

$pushEvent = function ($event, array $data) use ($app) {
    $push = new \Costlocker\Integrations\Sync\Queue\PushSyncRequest($app['events.logger']);
    $push($event, $data);
    return new JsonResponse([], 200);
};
    
$app
    ->post('/basecamp', function (Request $r) use ($pushEvent) {
        return $pushEvent(\Costlocker\Integrations\Entities\Event::MANUAL_SYNC, $r->request->all());
    })
    ->before($checkAuthorization('basecamp'));

$app
    ->post('/webhooks/handler', function (Request $r) use ($pushEvent) {
        $data = ['body' => json_decode($r->getContent(), true), 'headers' => $r->headers->all()];
        return $pushEvent(\Costlocker\Integrations\Entities\Event::WEBHOOK_SYNC, $data);
    });

return $app;
