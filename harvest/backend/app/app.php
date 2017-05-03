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

$authorizeHarvest = function () use ($app) {
    if (!$app['session']->get('harvest')) {
        return new JsonResponse(null, 401);
    }
};

$getHarvestUser = function () use ($app) {
    return new JsonResponse($app['session']->get('harvest')['account']);
};

$app
    ->get('/user', function () use ($app, $getHarvestUser) {
        if ($app['session']->get('harvest')) {
            return $getHarvestUser();
        }
        return new JsonResponse([], 404);
    });

$app
    ->get('/harvest', function (Request $r) use ($app) {
        $harvest = $app['session']->get('harvest');
        $apiClient = new Costlocker\Integrations\HarvestClient($harvest['account']['company_url'], $harvest['auth']);
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
            return new JsonResponse([
                'tasks' => array_map(
                    function (array $task) use ($taskPersons) {
                        return [
                            'id' => $task['task_id'],
                            'name' => $task['name'],
                            'total_hours' => $task['total_hours'],
                            'billed_rate' => $task['billed_rate'],
                            'people' => array_map(
                                function (array $person) {
                                    return [
                                        'id' => $person['user_id'],
                                        'user_name' => $person['full_name'],
                                        'total_hours' => $person['total_hours'],
                                        'cost_rate' => $person['cost_rate'],
                                        'billed_rate' => $person['billed_rate'],
                                        'projected_hours' => $person['projected_hours'],
                                    ];
                                },
                                $taskPersons[$task['task_id']]
                            ),
                        ];
                    },
                    $rawProject['tasks']
                ),
                'people' => array_map(
                    function (array $person) {
                        return [
                            'id' => $person['user_id'],
                            'user_name' => $person['full_name'],
                            'total_hours' => $person['total_hours'],
                            'billed_rate' => $person['billed_rate'],
                            'cost_rate' => $person['cost_rate'],
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
                            'to' => $formatDate($project['project']['hint_latest_record_at']),
                        ]),
                    ],
                ];
            },
            $apiClient("/projects")
        ));
    })->before($authorizeHarvest);

$app
    ->post('/harvest', function (Request $r) use ($app, $getHarvestUser) {
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
        return $getHarvestUser();
    });

$app->error(function (Exception $e) {
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return new JsonResponse(['errors' => [$e->getMessage()]], $e->getStatusCode());
    }
    return new JsonResponse(null, 500);
});
 
return $app;
