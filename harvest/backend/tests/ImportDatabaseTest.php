<?php

namespace Costlocker\Integrations\Sync;

use Mockery as m;
use Costlocker\Integrations\Auth\GetUser;

class ImportDatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testUnmappedCompany()
    {
        $database = new ImportDatabase(
            m::mock(GetUser::class)
                ->shouldReceive('getHarvestSubdomain')->once()->andReturn('company-that-was-never-imported')
                ->shouldReceive('getCostlockerCompanyId')->atLeast()->once()->andReturn('irrelevant-costlocker-id')
                ->getMock(),
            __DIR__ . '/fixtures'
        );
        $projects = [['id' => 123]];
        assertThat($database->separateProjectsByStatus($projects), is([
            ['id' => 123, 'status' => 'new'],
        ]));
        assertThat($database->getProjectId(123), is(emptyArray()));
        assertThat($database->getBilling(123, 'sent'), is(emptyArray()));
        assertThat($database->getExpense(123, 'irrelevant id'), is(emptyArray()));
        assertThat($database->getPerson(123, 'irrelevant task', 'irrelevant person'), is(emptyArray()));
        assertThat($database->getTimeentry(123, 'irrelevant composite key'), is(emptyArray()));
    }

    public function tearDown()
    {
        m::close();
    }
}
