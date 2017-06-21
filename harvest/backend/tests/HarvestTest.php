<?php

namespace Costlocker\Integrations\Harvest;

use Mockery as m;
use Costlocker\Integrations\HarvestClient;
use Symfony\Component\HttpFoundation\Request;

class HarvestTest extends \Costlocker\Integrations\GivenApi
{
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
        return $this->provideTaskFeesBudget() + [
            // BILLABLE WITHOUT BUDGET
            'no-budget + hours per person' => [
                'no-budget-person-hours.json',
                [
                    'Graphic Design' => ['rate' => 16932 / 77.13, 'hours' => 77.13, 'revenue' => 16932],
                    'Marketing' => ['rate' => 5087.50 / 27.5, 'hours' => 27.5, 'revenue' => 5087.50],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            'no-budget + hours per task' => [
                'no-budget-task-hours.json',
                [
                    'Graphic Design' => ['rate' => 38565 / 77.13, 'hours' => 77.13, 'revenue' => 38565],
                    'Marketing' => ['rate' => 11000 / 27.5, 'hours' => 27.5, 'revenue' => 11000],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            'no-budget + project hourly rate' => [
                'no-budget-project-rate.json',
                [
                    'Graphic Design' => ['rate' => 23139 / 77.13, 'hours' => 77.13, 'revenue' => 23139],
                    'Marketing' => ['rate' => 8250 / 27.5, 'hours' => 27.5, 'revenue' => 8250],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            'no-budget + no billing (detected as fixed fee)' => [
                'no-budget-no-billing.json',
                [
                    'Graphic Design' => ['rate' => 0 / 77.13, 'hours' => 77.13, 'revenue' => 0],
                    'Marketing' => ['rate' => 0 / 27.5, 'hours' => 27.5, 'revenue' => 0],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
            // FIXED FEE
            'fixed fee' => [
                'fixed-fee.json',
                [
                    'Programming' => ['rate' => 100000 / 62.20, 'hours' => 62.20, 'revenue' => 100000],
                    'Marketing' => ['rate' => 100000 / 17.98, 'hours' => 17.98, 'revenue' => 100000],
                ],
                200000,
            ],
            // NON-BILLABLE PROJECTS - no client rate, revenue
            'non-billable project + no budget' => [
                'non-billable-no-budget.json',
                [
                    'Project Management' => ['rate' => 0, 'hours' => 5.83, 'revenue' => 0],
                ],
            ],
            'non-billable project + total project hours' => [
                'non-billable-total-project-hours.json',
                [
                    'Project Management' => ['rate' => 0, 'hours' => 5, 'revenue' => 0],
                ],
            ],
            'non-billable project + hours per person' => [
                'non-billable-person-hours.json',
                [
                    'Project Management' => ['rate' => 0, 'hours' => 10 / 1, 'revenue' => 0],
                ],
            ],
            'non-billable project + hours per task' => [
                'non-billable-task-hours.json',
                [
                    'Project Management' => ['rate' => 0, 'hours' => 8 / 1, 'revenue' => 0],
                ],
            ],
        ];
    }

    // task_fees is used as budget (for calculating client rates), bill_by is ignored
    private function provideTaskFeesBudget()
    {
        $projects = [];
        $billingTypes = ['person-rate', 'task-rate', 'project-rate', 'no-rate'];
        foreach ($billingTypes as $billingType) {
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
