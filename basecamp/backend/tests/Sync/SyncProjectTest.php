<?php

namespace Costlocker\Integrations\Basecamp;

use Mockery as m;
use GuzzleHttp\Psr7\Response;
use Costlocker\Integrations\CostlockerClient;
use Costlocker\Integrations\Basecamp\Api\BasecampApi;

class SyncProjectTest extends \PHPUnit_Framework_TestCase
{
    private $costlocker;
    private $basecamp;
    private $database = [];

    protected function setUp()
    {
        $this->costlocker = m::mock(CostlockerClient::class);
        $this->basecamp = m::mock(BasecampApi::class);
    }

    public function testCreateProject()
    {
        $basecampId = 'irrelevant new id';
        $this->givenCostlockerProject('one-person.json');
        $this->basecamp->shouldReceive('createProject')->once()
            ->with('ACME | Website', null, null)
            ->andReturn($basecampId);
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com', 2 => 'peter@example.com']));
        $this->basecamp->shouldReceive('createTodolist')->once()
            ->with($basecampId, 'Development')
            ->andReturn($basecampId);
        $this->basecamp->shouldReceive('createTodo')->once()
            ->with($basecampId, $basecampId, 'Homepage', 1);
        $this->basecamp->shouldReceive('createTodo')->once()
            ->with($basecampId, $basecampId, 'Development', 2);
        $this->synchronize();
    }

    public function testPartialUpdate()
    {
        $basecampId = 'existing id';
        $this->database = [
            1 => [
                'id' => $basecampId,
                'activities' => [
                    1 => [
                        'id' => $basecampId,
                        'todos' => [
                            885 => $basecampId,
                        ],
                    ],
                ],
            ],
        ];
        $this->givenCostlockerProject('one-person.json');
        $this->basecamp->shouldReceive('createProject')->never();
        $this->basecamp->shouldReceive('grantAccess')->once()
            ->with($basecampId, [
                'John Doe (john@example.com)' => 'john@example.com',
                'Peter Nobody (peter@example.com)' => 'peter@example.com',
            ]);
        $this->basecamp->shouldReceive('getPeople')->once()
            ->andReturn($this->givenBasecampPeople([1 => 'john@example.com', 2 => 'peter@example.com']));
        $this->basecamp->shouldReceive('createTodolist')->never();
        $this->basecamp->shouldReceive('createTodo')->once();
        $this->synchronize();
    }

    private function givenCostlockerProject($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/{$file}");
        $this->costlocker->shouldReceive('__invoke')->andReturn(new Response(200, [], $json));
    }

    private function givenBasecampPeople(array $emails)
    {
        $people = [];
        foreach ($emails as $id => $email) {
            $people[$email] = (object) [
                'id' => $id,
                'name' => $email,
                'admin' => false,
            ];
        }
        return $people;
    }

    private function synchronize()
    {
        $basecampFactory = m::mock(BasecampFactory::class);
        $basecampFactory->shouldReceive('__invoke')->andReturn($this->basecamp);
        $uc = new SyncProject($this->costlocker, $basecampFactory, $this->database);
        $uc([
            'account' => 'irrelevant basecamp account',
            'costlockerProject' => 'irrelevant id',
        ]);
    }

    public function tearDown()
    {
        m::close();
    }
}
