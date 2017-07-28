<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
class Version20170728150758 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE fa_accounts RENAME COLUMN subjects TO data');
        $this->migrateAccountData("concat('{\"account\":null,\"subjects\":', data,'}')::json");
    }

    public function down(Schema $schema)
    {
        $this->migrateAccountData("data#>'{subjects}'");
        $this->addSql('ALTER TABLE fa_accounts RENAME COLUMN data TO subjects');
    }

    private function migrateAccountData($newValue)
    {
        $this->addSql("UPDATE fa_accounts SET data = {$newValue}");
    }
}
