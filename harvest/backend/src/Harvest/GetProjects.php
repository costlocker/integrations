<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;

class GetProjects
{
    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $clients = [];
        foreach ($apiClient("/clients") as $client) {
            $clients[$client['client']['id']] = $client['client']['name'];
        }

        $formatDate = function ($date) {
            return date('Ymd', strtotime($date));
        };

        return array_map(
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
        );
    }
}
