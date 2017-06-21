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
        return [
            'Person hourly rate + task fees' => [
                'person-hourly-rate-task-fees.json',
                [
                    'Graphic Design' => ['rate' => 45000 / 77.13, 'hours' => 77.13, 'revenue' => 45000],
                    'Marketing' => ['rate' => 20000 / 27.5, 'hours' => 27.5, 'revenue' => 20000],
                    'Project Management' => ['rate' => 0, 'hours' => 13.17, 'revenue' => 0],
                    'Business Development' => ['rate' => 0, 'hours' => 2.1, 'revenue' => 0],
                ],
            ],
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
