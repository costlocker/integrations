<?php

namespace Costlocker\Integrations;

class ImportDatabaseTest extends \PHPUnit_Framework_TestCase
{
    public function testUnmappedCompany()
    {
        $database = new ImportDatabase(__DIR__ . '/fixtures');
        $database->setHarvestAccount('company-that-was-never-imported');
        assertThat($database->getProjects(), is(emptyArray()));
    }
}
