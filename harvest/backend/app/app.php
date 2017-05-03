<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

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

$authorizeHarvest = function () use ($app) {
    if (!$app['session']->get('harvest')) {
        return new JsonResponse(null, 401);
    }
};

$getLoggedUser = function () use ($app) {
    return new JsonResponse([
        'harvest' => $app['session']->get('harvest')['account'] ?? null,
        'costlocker' => $app['session']->get('costlocker')['accessToken'] ?? null,
    ]);
};

$app
    ->get('/costlocker/login', function (Request $r) use ($app) {
        $appUrl = getenv('CL_FRONTED_URL');
        $costlockerHost = getenv('CL_HOST');
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => getenv('CL_CLIENT_ID'),
            'clientSecret' => getenv('CL_CLIENT_SECRET'),
            'redirectUri' => null,
            'urlAuthorize' => "{$costlockerHost}/api-public/oauth2/authorize",
            'urlAccessToken' => "{$costlockerHost}/api-public/oauth2/access_token",
            'urlResourceOwnerDetails' => "{$costlockerHost}/api-public/v2/",
        ]);
        $sendError = function ($error) use ($appUrl, $app) {
            $app['session']->remove('costlocker');
            $app['session']->remove('costlockerLogin');
            return new RedirectResponse("{$appUrl}?clLoginError={$error}");
        };
        if (!$r->query->get('code') && !$r->query->get('error')) {
            // getState must be called after getAuthorizationUrl
            $url = $provider->getAuthorizationUrl();
            $app['session']->set('costlockerLogin', [
                'oauthState' => $provider->getState(),
                'redirectUrl' => $appUrl,
            ]);
            return new RedirectResponse($url);
        } elseif ($r->query->get('state') != $app['session']->get('costlockerLogin')['oauthState']) {
            return $sendError('Invalid state');
        } elseif ($r->query->get('error')) {
            return $sendError($r->query->get('error'));
        } else {
            try {
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $r->query->get('code')
                ]);
                $app['session']->remove('costlockerLogin');
                $app['session']->set('costlocker', [
                    'accessToken' => $accessToken->jsonSerialize(),
                ]);
                return new RedirectResponse($appUrl);
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                return $sendError($e->getMessage());
            }
        }
    });

$app
    ->get('/user', function () use ($getLoggedUser) {
        return $getLoggedUser();
    });

$app
    ->get('/harvest', function (Request $r) use ($app) {
        $harvest = $app['session']->get('harvest');
        $apiClient = new Costlocker\Integrations\HarvestClient($harvest['account']['company_url'], $harvest['auth']);
        if ($r->query->get('billing')) {
            $dateStart = $r->query->get('from', date('Y0101'));
            $dateEnd = $r->query->get('to', date('Ymd'));
            $client = $r->query->get('client', '');
            $stats = [
                'issued' => 0,
                'invoiced' => 0,
            ];
            $invoices = array_map(
                function (array $invoice) use (&$stats) {
                    $isInvoiced = $invoice['invoices']['state'] == 'paid';
                    $amount = $invoice['invoices']['amount'];
                    if ($isInvoiced) {
                        $stats['invoiced'] += $amount;
                    } else {
                        $stats['issued'] += $amount;
                    }
                    return [
                        'id' => $invoice['invoices']['id'],
                        'description' =>
                            "#{$invoice['invoices']['number']}" .
                            ($invoice['invoices']['subject'] ? " {$invoice['invoices']['subject']}" : ''),
                        'total_amount' => $amount,
                        'date' => $invoice['invoices']['issued_at'],
                        'is_invoiced' => $isInvoiced,
                    ];
                },
                $apiClient("/invoices?from={$dateStart}&to={$dateEnd}&client={$client}&page=1")
            );
            return new JsonResponse([
                'stats' => $stats,
                'invoices' => $invoices,
            ]);
        }
        if ($r->query->get('expenses')) {
            $dateStart = $r->query->get('from', date('Y0101'));
            $dateEnd = $r->query->get('to', date('Ymd'));
            $expenses = $apiClient("/projects/{$r->query->get('expenses')}/expenses?from={$dateStart}&to={$dateEnd}");
            $categories = [];
            foreach ($apiClient("/expense_categories") as $client) {
                $categories[$client['expense_category']['id']] = $client['expense_category']['name'];
            }
            return new JsonResponse(array_map(
                function (array $expense) use ($categories) {
                    return [
                        'id' => $expense['expense']['id'],
                        'description' =>
                            "{$expense['expense']['units']}x " .
                            $categories[$expense['expense']['expense_category_id']] .
                            ($expense['expense']['notes'] ? " ({$expense['expense']['notes']})" : ''),
                        'purchased' => [
                            'total_amount' => $expense['expense']['total_cost'],
                            'date' => $expense['expense']['spent_at'],
                        ],
                        'billed' => [
                            'total_amount' => $expense['expense']['total_cost'],
                        ],
                    ];
                },
                $expenses
            ));
        }
        if ($r->query->get('peoplecosts')) {
            $rawProject = $apiClient("/projects/{$r->query->get('peoplecosts')}/analysis?period=lifespan");
            $taskPersons = [];
            foreach ($rawProject['tasks'] as $task) {
                $taskPersons[$task['task_id']] = $apiClient("/projects/{$r->query->get('peoplecosts')}/team_analysis?task_id={$task['task_id']}&period=lifespan");
            }
            $users = [];
            foreach ($apiClient('/people') as $person) {
                $users[$person['user']['id']] = [
                    'email' => $person['user']['email'],
                    'first_name' => $person['user']['first_name'],
                    'last_name' => $person['user']['last_name'],
                    'full_name' => "{$person['user']['first_name']} {$person['user']['last_name']}",
                    'role' => $person['user']['is_admin'] ? 'ADMIN' : 'EMPLOYEE',
                    'salary' => [
                        'payment' => 'hourly',
                        'hourly_rate' => $person['user']['cost_rate'],
                    ],
                ];
            }
            return new JsonResponse([
                'tasks' => array_map(
                    function (array $task) use ($taskPersons, $users) {
                        return [
                            'id' => $task['task_id'],
                            'activity' => [
                                'name' => $task['name'],
                                'hourly_rate' => $task['billed_rate'],
                            ],
                            'hours' => [
                                'tracked' => $task['total_hours'],
                            ],
                            'people' => array_map(
                                function (array $person) use ($users) {
                                    return [
                                        'finance' => [
                                            'billed_rate' => $person['billed_rate'],
                                        ],
                                        'hours' => [
                                            'budget' => $person['projected_hours'],
                                            'tracked' => $person['total_hours'],
                                        ],
                                        'person' => $users[$person['user_id']],
                                    ];
                                },
                                $taskPersons[$task['task_id']]
                            ),
                        ];
                    },
                    $rawProject['tasks']
                ),
                'people' => array_map(
                    function (array $person) use ($users) {
                        return [
                            'id' => $person['user_id'],
                            'finance' => [
                                'billed_rate' => $person['billed_rate'],
                            ],
                            'hours' => [
                                'budget' => $person['projected_hours'],
                                'tracked' => $person['total_hours'],
                            ],
                            'person' => $users[$person['user_id']],
                        ];
                    },
                    $rawProject['team_members']
                ),
            ]);
        }
        $clients = [];
        foreach ($apiClient("/clients") as $client) {
            $clients[$client['client']['id']] = $client['client']['name'];
        }
        $formatDate = function ($date) {
            return date('Ymd', strtotime($date));
        };
        return new JsonResponse(array_map(
            function (array $project) use ($clients, $formatDate) {
                $latestRecordPlusOneMonth = date(
                    'Y-m-d',
                    strtotime($project['project']['hint_latest_record_at'])
                    + 30 * 24 * 3600 // add one month to latest tracking
                );
                return [
                    'id' => $project['project']['id'],
                    'name' => $project['project']['name'],
                    'client' => [
                        'id' => $project['project']['client_id'],
                        'name' => $clients[$project['project']['client_id']],
                    ],
                    'dates' => [
                        'date_start' => $project['project']['starts_on'],
                        'date_end' => $project['project']['ends_on'],
                    ],
                    'finance' => [
                        'bill_by' => $project['project']['bill_by'],
                        'budget' => $project['project']['budget'],
                        'budget_by' => $project['project']['budget_by'],
                        'estimate' => $project['project']['estimate'],
                        'estimate_by' => $project['project']['estimate_by'],
                        'hourly_rate' => $project['project']['hourly_rate'],
                        'cost_budget' => $project['project']['cost_budget'],
                        'cost_budget_include_expenses' => $project['project']['cost_budget_include_expenses'],
                    ],
                    'links' => [
                        'peoplecosts' => "/harvest?peoplecosts={$project['project']['id']}",
                        'expenses' => "/harvest?" . http_build_query([
                            'expenses' => $project['project']['id'],
                            'from' => $formatDate($project['project']['hint_earliest_record_at']),
                            'to' => $formatDate($latestRecordPlusOneMonth),
                        ]),
                        'billing' => "/harvest?" . http_build_query([
                            'billing' => $project['project']['id'],
                            'client' => $project['project']['client_id'],
                            'from' => $formatDate($project['project']['hint_earliest_record_at']),
                            'to' => $formatDate($latestRecordPlusOneMonth),
                        ]),
                    ],
                ];
            },
            $apiClient("/projects")
        ));
    })->before($authorizeHarvest);

$app
    ->post('/harvest', function (Request $r) use ($app, $getLoggedUser) {
        $authHeader = 'Basic ' . base64_encode("{$r->request->get('username')}:{$r->request->get('password')}");
        $client = new Costlocker\Integrations\HarvestClient("https://{$r->request->get('domain', 'a')}.harvestapp.com", $authHeader);
        list($statusCode, $json) = $client("/account/who_am_i", true);
        if ($statusCode != 200) {
            return new JsonResponse([], $statusCode);
        }
        $app['session']->set('harvest', [
            'account' => [
                'company_name' => $json['company']['name'],
                'company_url' => $json['company']['base_uri'],
                'user_name' => "{$json['user']['first_name']} {$json['user']['last_name']}",
                'user_avatar' => $json['user']['avatar_url'],
            ],
            'auth' => $authHeader,
        ]);
        return $getLoggedUser();
    });

$app->error(function (Exception $e) {
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return new JsonResponse(['errors' => [$e->getMessage()]], $e->getStatusCode());
    }
    return new JsonResponse(null, 500);
});
 
return $app;
