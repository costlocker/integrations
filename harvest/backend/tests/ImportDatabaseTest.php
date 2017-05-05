<?php

namespace Costlocker\Integrations;

class ImportDatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testUnmappedCompany()
    {
        $database = new ImportDatabase(__DIR__ . '/fixtures');
        $database->setHarvestAccount('company-that-was-never-imported');
        $projects = [['id' => 123]];
        assertThat($database->separateProjectsByStatus($projects), is([
            'new' => $projects,
            'imported' => [],
        ]));
    }
}
