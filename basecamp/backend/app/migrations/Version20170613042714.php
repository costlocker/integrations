<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
class Version20170613042714 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE cl_companies ADD bc_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cl_companies ADD cl_user_id INT DEFAULT NULL');
        $this->addSql('
            ALTER TABLE cl_companies ADD CONSTRAINT FK_52851F39E572ECDB FOREIGN KEY (bc_user_id)
                REFERENCES bc_cl_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('
            ALTER TABLE cl_companies ADD CONSTRAINT FK_52851F39D5094834 FOREIGN KEY (cl_user_id)
                REFERENCES cl_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('CREATE INDEX IDX_52851F39E572ECDB ON cl_companies (bc_user_id)');
        $this->addSql('CREATE INDEX IDX_52851F39D5094834 ON cl_companies (cl_user_id)');

        $this->addSql('ALTER TABLE bc_projects ADD bc_webhook_id INT DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE cl_companies DROP CONSTRAINT FK_52851F39E572ECDB');
        $this->addSql('ALTER TABLE cl_companies DROP CONSTRAINT FK_52851F39D5094834');
        $this->addSql('DROP INDEX IDX_52851F39E572ECDB');
        $this->addSql('DROP INDEX IDX_52851F39D5094834');
        $this->addSql('ALTER TABLE cl_companies DROP bc_user_id');
        $this->addSql('ALTER TABLE cl_companies DROP cl_user_id');

        $this->addSql('ALTER TABLE bc_projects DROP bc_webhook_id');
    }
}
