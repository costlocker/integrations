<?php

namespace Costlocker\Integrations\Harvest;

use Symfony\Component\HttpFoundation\Request;
use Costlocker\Integrations\HarvestClient;
use Costlocker\Integrations\Sync\ImportDatabase;
use Costlocker\Integrations\Auth\GetUser;

class GetProjects
{
    private $database;
    private $getUser;

    public function __construct(ImportDatabase $d, GetUser $u)
    {
        $this->database = $d;
        $this->getUser = $u;
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function __invoke(Request $r, HarvestClient $apiClient)
    {
        $clients = [];
        foreach ($apiClient("/clients") as $client) {
            $clients[$client['client']['id']] = [
                'id' => $client['client']['id'],
                'name' => $client['client']['name'],
                'currency' => $client['client']['currency_symbol'],
            ];
        }

        $formatDate = function ($date) {
            return date('Ymd', strtotime($date));
        };

        $projects = array_map(
            function (array $project) use ($clients, $formatDate) {
                $latestRecordPlusOneMonth = date(
                    'Y-m-d',
                    strtotime($project['project']['hint_latest_record_at'])
                    + 30 * 24 * 3600 // add one month to latest tracking
                );
                $dateStart = [
                    $project['project']['starts_on'],
                    $project['project']['hint_earliest_record_at'],
                    date('Y-m-d', strtotime('today')),
                ];
                $dateEnd = [
                    $project['project']['ends_on'],
                    $project['project']['hint_earliest_record_at'],
                    date('Y-m-d', strtotime('tomorrow')),
                ];
                return [
                    'id' => $project['project']['id'],
                    'name' => $project['project']['name'],
                    'client' => $clients[$project['project']['client_id']],
                    'dates' => [
                        'date_start' => current(array_filter($dateStart)),
                        'date_end' => current(array_filter($dateEnd)),
                    ],
                    'finance' => [
                        'billable' => $project['project']['billable'],
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
                        'harvest' => "{$this->getUser->getHarvestUrl()}/projects/{$project['project']['id']}",
                    ],
                ];
            },
            $apiClient("/projects")
        );
        return $this->database->separateProjectsByStatus($projects);
    }
}
