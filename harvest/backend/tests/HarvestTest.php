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
            'Person hourly rate + task fees' => [
                'person-hourly-rate-task-fees.json',
                [
                    'Graphic Design' => ['rate' => 300, 'hours' => 34.16389559513506],
                    'Marketing' => ['rate' => 185, 'hours' => 18.018018018018019],
                    'Project Management' => ['rate' => 350, 'hours' => 0],
                    'Business Development' => ['rate' => 280, 'hours' => 0],
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
