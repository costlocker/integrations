<?php

namespace Costlocker\Integrations;

use Mockery as m;
use Costlocker\Integrations\Auth\GetUser;

class ImportDatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testUnmappedCompany()
    {
        $database = new ImportDatabase(
            m::mock(GetUser::class)
                ->shouldReceive('getHarvestSubdomain')->once()->andReturn('company-that-was-never-imported')
                ->getMock(),
            __DIR__ . '/fixtures'
        );
        $projects = [['id' => 123]];
        assertThat($database->separateProjectsByStatus($projects), is([
            'new' => $projects,
            'imported' => [],
        ]));
    }

    public function tearDown()
    {
        m::close();
    }
}
