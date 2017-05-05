<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Costlocker\Integrations\Api\ResponseHelper;

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
        $projectResponse = $this->client->__invoke("/projects/", $projectRequest);
        $timeentriesResponse = null;
        if ($projectResponse->getStatusCode() == 200) {
            $createdProject = $this->responseToJson($projectResponse)[0];
            $timeentries = $this->transformTimeentries($projectRequest, $createdProject);
            $timeentriesResponse = $this->client->__invoke("/timeentries/", $timeentries);
            $response = new JsonResponse([
                'projectUrl' => $this->client->getUrl("/projects/detail/{$createdProject['id']}/overview"),
            ]);
            $createdTimeentries = $this->responseToJson($timeentriesResponse);
            $this->database->saveProject($projectRequest, $createdProject, $createdTimeentries);
        } else {
            $response = ResponseHelper::error('Project creation has failed');
        }
        $this->log($r, $response, $projectResponse, $timeentriesResponse);
        return $response;
    }

    private function transformProject(array $harvestProject)
    {
        return [
            'name' => $harvestProject['selectedProject']['name'],
            'client' => $harvestProject['selectedProject']['client']['name'],
            'responsible_people' => [
                $this->client->getLoggedEmail(),
            ],
            'items' => array_merge(
                $this->transformPeopleCosts($harvestProject['peoplecosts']['tasks']),
                $this->transformExpenses($harvestProject['expenses']),
                $this->transformBilling($harvestProject['billing']['stats'])
            ),
            'harvest' => $harvestProject['selectedProject']['id'],
        ];
    }

    private function transformPeopleCosts(array $tasks)
    {
        $items = [];
        foreach ($tasks as $task) {
            foreach ($task['people'] as $person) {
                $items[] = [
                    'item' => 'person',
                    'activity' => $task['activity'],
                    'hours' => $person['hours'],
                    'person' => $person['person'],
                    'harvest' => [
                        'task' => $task['id'],
                        'user' => $person['person']['id'],
                    ],
                ];
            }
        }
        return $items;
    }

    private function transformExpenses(array $expenses)
    {
        $items = [];
        foreach ($expenses as $expense) {
            $harvestId = $expense['id'];
            unset($expense['id']);
            $items[] = [
                'item' => 'expense',
                'expense' => $expense,
                'harvest' => $harvestId,
            ];
        }
        return $items;
    }

    private function transformBilling(array $billingStats)
    {
        $items = [];
        foreach ($billingStats as $status => $amount) {
            $items[] = [
                'item' => 'billing',
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
            if (in_array($item['item'], ['billing', 'expense'])) {
                continue;
            }
            $items[] = [
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

    private function log(Request $r, JsonResponse $response, Response $projectResponse, Response $timeentriesResponse = null)
    {
        $responseToLog = function (Response $res = null) {
            if (!$res) {
                return null;
            }
            return [
                'status' => $res->getStatusCode(),
                'body' => (string) $res->getBody(),
            ];
        };
        $this->logger->log(
            $response->isOk() ? Logger::INFO : Logger::ERROR,
            'Import',
            [
                'request' => $r->getContent(),
                'response' => $response->getContent(),
                'projects' => $responseToLog($projectResponse),
                'timeentries' => $responseToLog($timeentriesResponse),
            ]
        );
    }
}
