<?php

namespace Costlocker\Integrations;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class HarvestToCostlocker
{
    private $client;
    private $session;
    private $domain;

    public function __construct(Client $c, SessionInterface $s, $domain)
    {
        $this->client = $c;
        $this->session = $s;
        $this->domain = $domain;
    }

    public function __invoke(Request $r)
    {
        $projectResponse = $this->call("/projects/", $this->transformProject($r));
        if ($projectResponse->getStatusCode() != 200) {
            return new JsonResponse([], 400);
        }
        $timeentriesResponse = $this->call("/timeentries/", $this->transformTimeentries($r));
        $projectId = 1;
        return new JsonResponse([
            'projectUrl' => "{$this->domain}/projects/detail/{$projectId}/overview"
        ]);
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

    private function transformTimeentries(Request $r)
    {
        $items = [];
        foreach ($r->request->get('peoplecosts')['tasks'] as $task) {
            foreach ($task['people'] as $person) {
                $items[] = [
                    'description' => "Harvest import",
                    'date' => date('Y-m-d'),
                    'duration' => $person['hours']['tracked'],
                    'assignment' => null, // ids from created project
                ];
            }
        }
        return $items;
    }

    private function call($path, array $json)
    {
        return $this->client->post("{$this->domain}/api-public/v2{$path}", [
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->session->get('costlocker')['accessToken']['access_token'],
            ],
            'json' => $json,
        ]);
    }
}
