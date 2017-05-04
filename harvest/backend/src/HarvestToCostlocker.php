<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Costlocker\Integrations\Api\ResponseHelper;

class HarvestToCostlocker
{
    private $client;
    private $session;
    private $logger;
    private $domain;

    public function __construct(Client $c, SessionInterface $s, Logger $l, $domain)
    {
        $this->client = $c;
        $this->session = $s;
        $this->logger = $l;
        $this->domain = $domain;
    }

    public function __invoke(Request $r)
    {
        $project = $this->transformProject($r);
        $projectResponse = $this->call("/projects/", $project);
        $timeentriesResponse = null;
        if ($projectResponse->getStatusCode() == 200) {
            $createdProject = json_decode($projectResponse->getBody(), true)['data'][0];
            $timeentries = $this->transformTimeentries($project, $createdProject);
            $timeentriesResponse = $this->call("/timeentries/", $timeentries);
            $response = new JsonResponse([
                'projectUrl' => "{$this->domain}/projects/detail/{$createdProject['id']}/overview"
            ]);
        } else {
            $response = ResponseHelper::error('Project creation has failed');
        }
        $this->log($r, $response, $projectResponse, $timeentriesResponse);
        return $response;
    }

    private function transformProject(Request $r)
    {
        $project = $r->request->get('selectedProject');
        return [
            'name' => $project['name'],
            'client' => $project['client']['name'],
            'responsible_people' => [
                $this->session->get('costlocker')['account']['person']['email'],
            ],
            'items' => array_merge(
                $this->transformPeopleCosts($r->request->get('peoplecosts')['tasks']),
                $this->transformExpenses($r->request->get('expenses')),
                $this->transformBilling($r->request->get('billing')['stats'])
            ),
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
                ];
            }
        }
        return $items;
    }

    private function transformExpenses(array $expenses)
    {
        $items = [];
        foreach ($expenses as $expense) {
            unset($expense['id']);
            $items[] = [
                'item' => 'expense',
                'expense' => $expense,
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

    private function call($path, array $json)
    {
        $accessToken = $this->session->get('costlocker')['accessToken']['access_token'];
        return $this->client->post("{$this->domain}/api-public/v2{$path}", [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$accessToken}",
            ],
            'json' => $json,
        ]);
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
