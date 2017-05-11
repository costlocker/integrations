<?php

namespace Costlocker\Integrations\Sync;

use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Api\ResponseHelper;
use Costlocker\Integrations\CostlockerClient;

class HarvestToCostlocker
{
    private $client;
    private $database;
    private $logger;

    public function __construct(CostlockerClient $c, ImportDatabase $d, Logger $l)
    {
        $this->client = $c;
        $this->database = $d;
        $this->logger = $l;
    }

    public function __invoke(Request $r)
    {
        $projectRequest = $this->transformProject($r->request->all());
        $requests = [$projectRequest];
        $projectResponse = $this->client->__invoke("/projects/", $projectRequest);
        $timeentriesResponse = null;
        if ($projectResponse->getStatusCode() == 200) {
            $createdProject = $this->responseToJson($projectResponse)[0];
            $timeentries = $this->transformTimeentries($projectRequest, $createdProject);
            $requests[] = $timeentries;
            $timeentriesResponse = $this->client->__invoke("/timeentries/", $timeentries);
            $response = new JsonResponse([
                'projectUrl' => $this->client->getUrl("/projects/detail/{$createdProject['id']}/overview"),
            ]);
            $createdTimeentries = $this->responseToJson($timeentriesResponse);
            $this->database->saveProject($projectRequest, $createdProject, $createdTimeentries);
        } else {
            $apiError = json_decode($projectResponse->getBody(), true)['errors'][0] ?? [];
            $error = $apiError ? "{$apiError['title']} ({$apiError['detail']})" : 'Project import has failed';
            $response = ResponseHelper::error($error);
        }
        $this->log($r, $requests, $response, $projectResponse, $timeentriesResponse);
        return $response;
    }

    private function transformProject(array $harvestProject)
    {
        $harvestId = $harvestProject['selectedProject']['id'];
        return $this->database->getProjectId($harvestId) + [
            'name' => $harvestProject['selectedProject']['name'],
            'client' => $harvestProject['selectedProject']['client']['name'],
            'dates' => $harvestProject['selectedProject']['dates'],
            'responsible_people' => [
                $this->client->getLoggedEmail(),
            ],
            'items' => array_merge(
                $this->transformPeopleCosts($harvestId, $harvestProject['peoplecosts']['tasks']),
                $this->transformExpenses($harvestId, $harvestProject['expenses']),
                $this->transformBilling($harvestId, $harvestProject['billing']['stats'])
            ),
            'harvest' => $harvestId,
        ];
    }

    private function transformPeopleCosts($projectId, array $tasks)
    {
        $items = [];
        foreach ($tasks as $task) {
            foreach ($task['people'] as $person) {
                $items[] = [
                    'item' => ['type' => 'person'] + $this->database->getPerson($projectId, $task['id'], $person['id']),
                    'activity' => $task['activity'],
                    'hours' => $person['hours'],
                    'person' => $person['person'],
                    'harvest' => [
                        'task' => $task['id'],
                        'user' => $person['id'],
                        'timeentry' => "{$task['id']}_{$person['id']}",
                    ],
                ];
            }
        }
        return $items;
    }

    private function transformExpenses($projectId, array $expenses)
    {
        $items = [];
        foreach ($expenses as $expense) {
            $harvestId = $expense['id'];
            unset($expense['id']);
            $items[] = [
                'item' => ['type' => 'expense'] + $this->database->getExpense($projectId, $harvestId),
                'expense' => $expense,
                'harvest' => $harvestId,
            ];
        }
        return $items;
    }

    private function transformBilling($projectId, array $billingStats)
    {
        $items = [];
        foreach ($billingStats as $status => $amount) {
            $items[] = [
                'item' => ['type' => 'billing'] + $this->database->getBilling($projectId, $status),
                'billing' => [
                    'description' => "Harvest import - {$status}",
                    'total_amount' => $amount,
                    'date' => date('Y-m-d'),
                    'status' => $status
                ],
                'harvest' => $status,
            ];
        }
        return $items;
    }

    private function transformTimeentries(array $projectRequest, array $createdProject)
    {
        $items = [];
        foreach ($projectRequest['items'] as $index => $item) {
            if (in_array($item['item']['type'], ['billing', 'expense'])) {
                continue;
            }
            $uuid = $this->database->getTimeentry($projectRequest['harvest'], $item['harvest']['timeentry']);
            $items[] = $uuid + [
                'description' => "Harvest import",
                'date' => date('Y-m-d 00:00:00'),
                'duration' => round($item['hours']['tracked'] * 3600),
                'assignment' => ['project_id' => $createdProject['id']] + $createdProject['items'][$index]['item'],
            ];
        }
        return $items;
    }

    private function responseToJson(Response $r)
    {
        if ($r->getStatusCode() == 200) {
            return json_decode($r->getBody(), true)['data'];
        }
        return [];
    }

    private function log(
        Request $r,
        array $requests,
        JsonResponse $response,
        Response $projectResponse,
        Response $timeentriesResponse = null
    ) {
        $responseToLog = function (Response $res = null) {
            if (!$res) {
                return null;
            }
            return [
                'status' => $res->getStatusCode(),
                'body' => (string) $res->getBody(),
            ];
        };
        $isPsrOk = function (Response $res = null) {
            return $res && $res->getStatusCode() == 200;
        };
        $this->logger->log(
            $isPsrOk($projectResponse)
                ? ($isPsrOk($timeentriesResponse) ? Logger::INFO : Logger::WARNING)
                : Logger::ERROR,
            'Import',
            [
                'requests' => [
                    'app' => $r->getContent(),
                    'costlocker' => $requests,
                ],
                'responses' => [
                    'app' => $response->getContent(),
                    'projects' => $responseToLog($projectResponse),
                    'timeentries' => $responseToLog($timeentriesResponse),
                ],
            ]
        );
    }
}
