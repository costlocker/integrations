<?php

namespace Costlocker\Integrations\Harvest;

use Mockery as m;
use Costlocker\Integrations\HarvestClient;
use Symfony\Component\HttpFoundation\Request;

class HarvestTest extends \Costlocker\Integrations\GivenApi
{
    /** @dataProvider provideProjects */
    public function testBuildPeopleCostsFromTasksAndPeople($file, array $expectedActivities)
    {
        $apiClient = $this->givenApiResponses($file);
        $costs = new GetPeopleCosts();
        $peopleCosts = $costs(new Request(), $apiClient);
        $this->assertEquals($expectedActivities, $this->normalizePeopleCosts($peopleCosts));
    }

    public function provideProjects()
    {
        return [
            'Task hourly rate + no budget' => [
                'task+no-budget.json',
                [
                    'Business Development' => ['rate' => 0, 'hours' => 0],
                    'Programming' => ['rate' => 100, 'hours' => 0],
                    'Project Management' => ['rate' => 200, 'hours' => 0],
                ],
            ],
            'Task hourly rate + total hours' => [
                'task+total-hours.json',
                [
                    'Business Development' => ['rate' => 0, 'hours' => 360 / 3],
                    'Programming' => ['rate' => 100, 'hours' => 360 / 3],
                    'Project Management' => ['rate' => 200, 'hours' => 360 / 3],
                ],
            ],
            'Task hourly rate + hours per task' => [
                'task+task-hours.json',
                [
                    'Business Development' => ['rate' => 0, 'hours' => 100 / 1],
                    'Project Management' => ['rate' => 100, 'hours' => 200 / 1],
                ],
            ],
            'Task hourly rate + hours per person' => [
                'task+person-hours.json',
                [
                    'Business Development' => ['rate' => 0, 'hours' => 50 / 3],
                    'Graphic Design' => ['rate' => 100, 'hours' => 50 / 3],
                    'Project Management' => ['rate' => 200, 'hours' => 50 / 3],
                ],
            ],
            'Task hourly rate + fees per task' => [
                'task+fees.json',
                [
                    'Business Development' => ['rate' => 0, 'hours' => 0],
                    'Programming' => ['rate' => 100, 'hours' => (20000 / 100)],
                    'Project Management' => ['rate' => 200, 'hours' => (30000 / 200)],
                ],
            ],
            'No hourly rate + some budget (e.g. task)' => [
                'no-hourly-rate.json',
                [
                    'Programming' => ['rate' => 0, 'hours' => 200 / 1],
                    'Project Management' => ['rate' => 0, 'hours' => 10 / 1],
                ],
            ],
            'Project hourly rate + some budget (e.g. task)' => [
                'project-hourly-rate.json',
                [
                    'Programming' => ['rate' => 200, 'hours' => 100 / 1],
                    'Project Management' => ['rate' => 200, 'hours' => 200 / 1],
                ],
            ],
            'Person hourly rate + some budget (e.g. task)' => [
                'person-hourly-rate.json',
                [
                    'Programming' => ['rate' => 400, 'hours' => 20 / 1],
                    'Project Management' => ['rate' => 400, 'hours' => 100 / 1],
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
            foreach ($activity['people'] as $person) {
                $activities[$activityName]['hours'] = $person['hours']['budget'];
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
