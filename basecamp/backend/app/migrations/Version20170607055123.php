<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170607055123 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE bc_project_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
            CREATE TABLE bc_project (
                id INT NOT NULL, 
                cl_project_id INT NOT NULL, 
                bc_account_id INT NOT NULL, 
                bc_project_id INT NOT NULL, 
                settings JSON NOT NULL, 
                mapping JSON NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE INDEX IDX_6DAFEFA08FB48F93 ON bc_project (cl_project_id)');
        $this->addSql('CREATE INDEX IDX_6DAFEFA0ADB08941 ON bc_project (bc_account_id)');
        $this->addSql(<<<SQL
            CREATE TABLE cl_company (
                id INT NOT NULL, 
                settings JSON, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql(<<<SQL
            CREATE TABLE cl_project (
                id INT NOT NULL, 
                cl_company_id INT NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE INDEX IDX_5DD44B4FE428AD9 ON cl_project (cl_company_id)');
        $this->addSql(<<<SQL
            ALTER TABLE bc_project ADD CONSTRAINT FK_6DAFEFA08FB48F93 FOREIGN KEY (cl_project_id)
                REFERENCES cl_project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
        $this->addSql(<<<SQL
            ALTER TABLE bc_project ADD CONSTRAINT FK_6DAFEFA0ADB08941 FOREIGN KEY (bc_account_id)
                REFERENCES bc_account (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
        $this->addSql(<<<SQL
            ALTER TABLE cl_project ADD CONSTRAINT FK_5DD44B4FE428AD9 FOREIGN KEY (cl_company_id)
                REFERENCES cl_company (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );

        $this->addSql(<<<SQL
            INSERT INTO cl_company(id, created_at)
            SELECT DISTINCT id_tenant, NOW()
            FROM cl_user
SQL
        );

        $this->addSql('DROP INDEX cl_user_tenant');
        $this->addSql('DROP INDEX cl_unique_user');
        $this->addSql('ALTER TABLE cl_user RENAME COLUMN id_tenant TO cl_company_id');
        $this->addSql(<<<SQL
            ALTER TABLE cl_user ADD CONSTRAINT FK_4E901768E428AD9 FOREIGN KEY (cl_company_id)
                REFERENCES cl_company (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
        $this->addSql('CREATE INDEX IDX_4E901768E428AD9 ON cl_user (cl_company_id)');
        $this->addSql('CREATE UNIQUE INDEX cl_unique_user ON cl_user (email, cl_company_id)');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE cl_user DROP CONSTRAINT FK_4E901768E428AD9');
        $this->addSql('DROP INDEX IDX_4E901768E428AD9');
        $this->addSql('DROP INDEX cl_unique_user');
        $this->addSql('ALTER TABLE cl_user RENAME COLUMN cl_company_id TO id_tenant');
        $this->addSql('CREATE INDEX cl_user_tenant ON cl_user (id_tenant)');
        $this->addSql('CREATE UNIQUE INDEX cl_unique_user ON cl_user (email, id_tenant)');

        $this->addSql('ALTER TABLE cl_project DROP CONSTRAINT FK_5DD44B4FE428AD9');
        $this->addSql('ALTER TABLE bc_project DROP CONSTRAINT FK_6DAFEFA08FB48F93');
        $this->addSql('DROP SEQUENCE bc_project_id_seq CASCADE');
        $this->addSql('DROP TABLE bc_project');
        $this->addSql('DROP TABLE cl_company');
        $this->addSql('DROP TABLE cl_project');
    }
}
