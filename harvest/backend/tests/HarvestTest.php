<?php

namespace Costlocker\Integrations\Harvest;

use Mockery as m;
use Costlocker\Integrations\HarvestClient;
use Symfony\Component\HttpFoundation\Request;

class HarvestTest extends \Costlocker\Integrations\GivenApi
{
    private $billingTypes = ['person-rate', 'task-rate', 'project-rate', 'no-rate'];

    /** @dataProvider provideProjects */
    public function testBuildPeopleCostsFromTasksAndPeople($file, array $expectedActivities, $fixedBudget = 0)
    {
        $apiClient = $this->givenApiResponses($file);
        $costs = new GetPeopleCosts();
        $peopleCosts = $costs(new Request(['fixedBudget' => $fixedBudget]), $apiClient);
        $this->assertEquals($expectedActivities, $this->normalizePeopleCosts($peopleCosts));
    }

    public function provideProjects()
    {
        return
            $this->provideTaskFeesBudget() +
            $this->provideProjectCostBudget() +
            $this->provideBillableWithoutBudget('person-rate', 16932, 5087.50) +
            $this->provideBillableWithoutBudget('task-rate', 38565, 11000) +
            $this->provideBillableWithoutBudget('project-rate', 23139, 8250) +
            $this->provideBillableWithoutBudget('no-rate', 0, 0) +
            $this->provideFixedFee() +
            $this->provideNonBillableBudget('project-hours', 5 / 1) +
            $this->provideNonBillableBudget('person-hours', 10 / 1) +
            $this->provideNonBillableBudget('task-hours', 8 / 1) +
            $this->provideNonBillableBudget('no-budget', 5.83) +
            $this->provideHoursBudget('project') +
            $this->provideHoursBudget('task') +
            $this->provideHoursBudget('person');
    }

    // task_fees is used as budget (for calculating client rates), bill_by is ignored
    private function provideTaskFeesBudget()
    {
        $projects = [];
        foreach ($this->billingTypes as $billingType) {
            $projects["task_fees + {$billingType}.json"] = [
                "task-fees-{$billingType}.json",
                [
                    'Graphic Design' => ['rate' => 45000 / 77.13, 'hours' => 77.13, 'revenue' => 45000],
                    'Marketing' => ['rate' => 20000 / 27.5, 'hours' => 27.5, 'revenue' => 20000],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ];
        }
        return $projects;
    }

    // project cost is used as budget (for calculating client rates), bill_by is ignored
    private function provideProjectCostBudget()
    {
        $projects = [];
        foreach ($this->billingTypes as $billingType) {
            $projects["project cost + {$billingType}.json"] = [
                "project-cost-{$billingType}.json",
                [
                    'Graphic Design' => ['rate' => 100000 / 77.13, 'hours' => 77.13, 'revenue' => 100000],
                    'Marketing' => ['rate' => 100000 / 27.5, 'hours' => 27.5, 'revenue' => 100000],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ];
        }
        return $projects;
    }

    // tracked hours * harvest are used for calculating client rates
    private function provideBillableWithoutBudget($billingType, $designRevenue, $marketingRevenue)
    {
        return [
            "no-budget + {$billingType}.json" => [
                "no-budget-{$billingType}.json",
                [
                    'Graphic Design' =>
                        ['rate' => $designRevenue / 77.13, 'hours' => 77.13, 'revenue' => $designRevenue],
                    'Marketing' =>
                        ['rate' => $marketingRevenue / 27.5, 'hours' => 27.5, 'revenue' => $marketingRevenue],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
        ];
    }

    // fixed fee is divided into billed tasks (just like in project_cost)
    private function provideFixedFee()
    {
        return [
            'fixed fee' => [
                'fixed-fee.json',
                [
                    'Programming' => ['rate' => 100000 / 62.20, 'hours' => 62.20, 'revenue' => 100000],
                    'Marketing' => ['rate' => 100000 / 17.98, 'hours' => 17.98, 'revenue' => 100000],
                ],
                200000,
            ],
        ];
    }

    // task_fees is used as budget (for calculating client rates), bill_by is ignored
    private function provideNonBillableBudget($budgetType, $estimatedHours)
    {
        return [
            "non-billable project + {$budgetType}" => [
                "non-billable-{$budgetType}.json",
                [
                    'Project Management' => ['rate' => 0, 'hours' => $estimatedHours, 'revenue' => 0],
                ],
            ],
        ];
    }

    // rate + tracked time is used (time budget are ignored, we want ot have same billable amount)
    private function provideHoursBudget($type)
    {
        return [
            "{$type} hours + project rate" => [
                "{$type}-hours-project-rate.json",
                [
                    'Graphic Design' => ['rate' => 300, 'hours' => 77.13, 'revenue' => 300 * 77.13],
                    'Marketing' => ['rate' => 300, 'hours' => 27.5, 'revenue' => 300 * 27.5],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            "{$type} hours + task rate" => [
                "{$type}-hours-task-rate.json",
                [
                    'Graphic Design' => ['rate' => 400.0, 'hours' => 77.13, 'revenue' => 400 * 77.13],
                    'Marketing' => ['rate' => 300.0, 'hours' => 27.5, 'revenue' => 300 * 27.5],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            "{$type} hours + person rate" => [
                "{$type}-hours-person-rate.json",
                [
                    'Graphic Design' => ['rate' => 16932 / 77.13, 'hours' => 77.13, 'revenue' => 16932],
                    'Marketing' => ['rate' => 5087.5 / 27.5, 'hours' => 27.5, 'revenue' => 5087.5],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            "{$type} hours + no rate" => [
                "{$type}-hours-no-rate.json",
                [
                    'Graphic Design' => ['rate' => 0, 'hours' => 77.13, 'revenue' => 0],
                    'Marketing' => ['rate' => 0, 'hours' => 27.5, 'revenue' => 0],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
        ];
    }
    

    private function givenApiResponses($file)
    {
        $json = json_decode(file_get_contents(__DIR__ . "/fixtures/harvest/{$file}"), true);
        $apiClient = m::mock(HarvestClient::class);
        $apiClient->shouldReceive('__invoke')->andReturnValues(array_merge(
            [['project' => $json['project']]],
            [$json['analysis']],
            $json['tasks'],
            [$json['people']]
        ));
        return $apiClient;
    }

    private function normalizePeopleCosts(array $peopleCosts)
    {
        $activities = [];
        foreach ($peopleCosts['tasks'] as $activity) {
            $activityName = $activity['activity']['name'];
            $activities[$activityName]['rate'] = $activity['activity']['hourly_rate'];
            $activities[$activityName]['revenue'] = $activity['finance']['revenue'];
            foreach ($activity['people'] as $person) {
                if (!isset($activities[$activityName]['hours'])) {
                    $activities[$activityName]['hours'] = 0;
                }
                $activities[$activityName]['hours'] += $person['hours']['budget'];
            }
        }
        return $activities;
    }

    public function testUnauthorizedRequest()
    {
        $response = $this->request([
            'method' => 'GET',
            'url' => '/harvest',
        ]);
        assertThat($response->getStatusCode(), is(401));
    }

    public function tearDown()
    {
        m::close();
    }
}
