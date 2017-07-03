<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\Api\ResponseHelper;

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

$app['client.fakturoid'] = function ($app) {
    return new Costlocker\Integrations\FakturoidClient($app['guzzle'], $app['client.user'], $app['logger']);
};

$app['database'] = function ($app) {
    return new \Costlocker\Integrations\Database\Database($app['orm.em']);
};

$app['redirectUrls'] = function ($app) {
    return new \Costlocker\Integrations\Auth\RedirectToApp($app['session'], getenv('APP_FRONTED_URL'));
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
    return new Costlocker\Integrations\Auth\GetUser($app['session'], $app['database']);
};

$app['client.check'] = function ($app) {
    return new Costlocker\Integrations\Auth\CheckAuthorization(
        $app['session'],
        $app['client.costlocker'],
        $app['client.fakturoid']
    );
};

$app['fakturoid.downloadSubjects'] = function ($app) {
    return new Costlocker\Integrations\Fakturoid\DownloadSubjects(
        $app['client.fakturoid'],
        $app['database']
    );
};

$checkAuthorization = function ($service) use ($app) {
    // prevents 'Cannot override frozen service "guzzle"'
    return function () use ($service, $app) {
        return $app['client.check']->checkAccount($service);
    };
};

$app
    ->get('/', function (Request $r) {
        return new JsonResponse([]);
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
        return $app['client.user']($isAddonDisabled);
    })
    ->before(function (Request $r, $app) {
        $app['redirectUrls']->loadInvoiceFromRequest($r);
    });

$app
    ->get('/oauth/costlocker', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Auth\AuthorizeInCostlocker(
            $app['session'],
            $app['oauth.costlocker'],
            new Costlocker\Integrations\Database\PersistCostlockerUser($app['database']),
            $app['logger'],
            $app['redirectUrls']
        );
        return $strategy($r);
    });

$app
    ->post('/oauth/fakturoid', function (Request $r) use ($app) {
        $strategy = new Costlocker\Integrations\Auth\AuthorizeInFakturoid(
            $app['client.fakturoid'],
            $app['session'],
            new Costlocker\Integrations\Database\PersistFakturoidUser($app['database'], $app['client.user']),
            $app['redirectUrls']
        );
        return $strategy($r);
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->get('/costlocker', function (Request $r) use ($app) {
        if ($r->query->has('invoice')) {
            $strategy = new \Costlocker\Integrations\Costlocker\GetInvoice(
                $app['client.costlocker'],
                $app['database']
            );
        } else {
            $strategy = new \Costlocker\Integrations\Costlocker\GetCreatedInvoices(
                $app['database'],
                $app['client.user']
            );
        }
        $data = $strategy($r);
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('costlocker'));

$app
    ->get('/fakturoid', function () use ($app) {
        $strategy = new Costlocker\Integrations\Fakturoid\GetSubjects(
            $app['client.user'],
            $app['fakturoid.downloadSubjects']
        );
        $data = $strategy();
        return new JsonResponse($data);
    })
    ->before($checkAuthorization('fakturoid'));

$app
    ->post('/fakturoid', function (Request $r) use ($app) {
        $action = $r->query->get('action');
        if ($action == 'createInvoice') {
            $strategy = new Costlocker\Integrations\Fakturoid\CreateInvoice(
                $app['client.fakturoid'],
                $app['client.user'],
                new \Costlocker\Integrations\Costlocker\MarkSentInvoice($app['client.costlocker']),
                $app['database']
            );
        } elseif ($action == 'createSubject') {
            $strategy = new Costlocker\Integrations\Fakturoid\CreateSubject(
                $app['client.fakturoid'],
                $app['client.user'],
                $app['database']
            );
        } elseif ($action == 'downloadSubjects') {
            $strategy = function () use ($app) {
                $account = $app['client.user']->getFakturoidAccount();
                $app['fakturoid.downloadSubjects']($account);
                return new JsonResponse();
            };
        } else {
            $strategy = function () use ($action) {
                return ResponseHelper::error("Not supported action '{$action}'");
            };
        }
        return $strategy($r);
    })
    ->before($checkAuthorization('fakturoid'));

return $app;
