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

$app
    ->get('/harvest', function (Request $r) use ($app) {
        $harvest = $app['session']->get('harvest');
        $client = new GuzzleHttp\Client();
        if ($r->query->get('peoplecosts')) {
            $response = $client->get("{$harvest['account']['company_url']}/projects/{$r->query->get('peoplecosts')}/analysis?period=lifespan", [
                'http_errors' => false,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => $harvest['auth'],
                ],
            ]);
            $rawProject = json_decode($response->getBody(), true);
            $taskPersons = [];
            foreach ($rawProject['tasks'] as $task) {
                $taskResponse = $client->get("{$harvest['account']['company_url']}/projects/{$r->query->get('peoplecosts')}/team_analysis?task_id={$task['task_id']}&period=lifespan", [
                    'http_errors' => false,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => $harvest['auth'],
                    ],
                ]);
                $taskPersons[$task['task_id']] = json_decode($taskResponse->getBody(), true);
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
        $response = $client->get("{$harvest['account']['company_url']}/projects", [
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $harvest['auth'],
            ],
        ]);
        $clientsResponse = $client->get("{$harvest['account']['company_url']}/clients", [
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $harvest['auth'],
            ],
        ]);
        $clients = [];
        foreach (json_decode($clientsResponse->getBody(), true) as $client) {
            $clients[$client['client']['id']] = $client['client']['name'];
        }
        return new JsonResponse(array_map(
            function (array $project) use ($clients) {
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
                ];
            },
            json_decode($response->getBody(), true)
        ));
    })->before($authorizeHarvest);

$app
    ->post('/harvest', function (Request $r) use ($app) {
        $client = new GuzzleHttp\Client();
        $authHeader = 'Basic ' . base64_encode("{$r->request->get('username')}:{$r->request->get('password')}");
        $response = $client->get("https://{$r->request->get('domain', 'a')}.harvestapp.com/account/who_am_i", [
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $authHeader,
            ],
        ]);
        $json = json_decode($response->getBody(), true);
        if ($response->getStatusCode() != 200) {
            return new JsonResponse([], $response->getStatusCode());
        }
        $account = [
            'company_name' => $json['company']['name'],
            'company_url' => $json['company']['base_uri'],
            'user_name' => "{$json['user']['first_name']} {$json['user']['last_name']}",
            'user_avatar' => $json['user']['avatar_url'],
        ];
        $app['session']->set('harvest', [
            'account' => $account,
            'auth' => $authHeader,
        ]);
        return new JsonResponse($account);
    });

$app->error(function (Exception $e) {
    if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
        return new JsonResponse(['errors' => [$e->getMessage()]], $e->getStatusCode());
    }
    return new JsonResponse(null, 500);
});
 
return $app;
