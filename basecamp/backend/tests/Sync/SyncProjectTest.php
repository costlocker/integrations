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

    protected function setUp()
    {
        $this->costlocker = m::mock(CostlockerClient::class);
        $this->basecamp = m::mock(BasecampApi::class);
    }

    public function testCreateProject()
    {
        $this->givenCostlockerProject('no-activities.json');
        $this->basecamp->shouldReceive('createProject')->once()->with('ACME | Website', null, null);
        $this->synchronize();
    }

    public function givenCostlockerProject($file)
    {
        $json = file_get_contents(__DIR__ . "/fixtures/{$file}");
        $this->costlocker->shouldReceive('__invoke')->andReturn(new Response(200, [], $json));
    }

    private function synchronize()
    {
        $basecampFactory = m::mock(BasecampFactory::class);
        $basecampFactory->shouldReceive('__invoke')->andReturn($this->basecamp);
        $uc = new SyncProject($this->costlocker, $basecampFactory);
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
