<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170608152923 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE oauth2_tokens_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE bc_projects_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE bc_cl_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE cl_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE events_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('
            CREATE TABLE oauth2_tokens (
                id INT NOT NULL, 
                cl_user_id INT NOT NULL, 
                bc_identity_id INT DEFAULT NULL, 
                access_token TEXT NOT NULL, 
                refresh_token TEXT NOT NULL, 
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_B81EC12D5094834 ON oauth2_tokens (cl_user_id)');
        $this->addSql('
            CREATE TABLE bc_accounts (
                id INT NOT NULL, 
                name VARCHAR(255) NOT NULL, 
                product VARCHAR(255) NOT NULL, 
                url_api VARCHAR(255) NOT NULL, 
                url_app VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('
            CREATE TABLE bc_projects (
                id INT NOT NULL, 
                cl_project_id INT NOT NULL, 
                bc_user_id INT DEFAULT NULL, 
                bc_project_id INT NOT NULL, 
                settings JSON NOT NULL, 
                mapping JSON NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_CDB5C30C8FB48F93 ON bc_projects (cl_project_id)');
        $this->addSql('CREATE INDEX IDX_CDB5C30CE572ECDB ON bc_projects (bc_user_id)');
        $this->addSql('
            CREATE TABLE bc_cl_users (
                id INT NOT NULL, 
                cl_user_id INT NOT NULL, 
                bc_account_id INT NOT NULL, 
                bc_identity_id INT NOT NULL, 
                data JSON NOT NULL, 
                deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_C90FC6DED5094834 ON bc_cl_users (cl_user_id)');
        $this->addSql('CREATE INDEX IDX_C90FC6DEADB08941 ON bc_cl_users (bc_account_id)');
        $this->addSql('
            CREATE TABLE cl_companies (
                id INT NOT NULL, 
                settings JSON DEFAULT NULL, 
                url_webhook VARCHAR(255) DEFAULT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('
            CREATE TABLE cl_projects (
                id INT NOT NULL, 
                cl_company_id INT NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_FD304741E428AD9 ON cl_projects (cl_company_id)');
        $this->addSql('
            CREATE TABLE cl_users (
                id INT NOT NULL, 
                cl_company_id INT NOT NULL, 
                email VARCHAR(255) NOT NULL, 
                data JSON NOT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_5829B676E428AD9 ON cl_users (cl_company_id)');
        $this->addSql('CREATE UNIQUE INDEX cl_unique_user ON cl_users (email, cl_company_id)');
        $this->addSql('
            CREATE TABLE events (
                id INT NOT NULL, 
                cl_user_id INT DEFAULT NULL, 
                bc_project_id INT DEFAULT NULL, 
                event INT NOT NULL, 
                data JSON NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_5387574AD5094834 ON events (cl_user_id)');
        $this->addSql('CREATE INDEX IDX_5387574A20B6C967 ON events (bc_project_id)');
        $this->addSql('
            ALTER TABLE oauth2_tokens ADD CONSTRAINT FK_B81EC12D5094834 FOREIGN KEY (cl_user_id)
            REFERENCES cl_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE bc_projects ADD CONSTRAINT FK_CDB5C30C8FB48F93 FOREIGN KEY (cl_project_id)
            REFERENCES cl_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE bc_projects ADD CONSTRAINT FK_CDB5C30CE572ECDB FOREIGN KEY (bc_user_id)
            REFERENCES bc_cl_users (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE bc_cl_users ADD CONSTRAINT FK_C90FC6DED5094834 FOREIGN KEY (cl_user_id)
            REFERENCES cl_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE bc_cl_users ADD CONSTRAINT FK_C90FC6DEADB08941 FOREIGN KEY (bc_account_id)
            REFERENCES bc_accounts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE cl_projects ADD CONSTRAINT FK_FD304741E428AD9 FOREIGN KEY (cl_company_id)
            REFERENCES cl_companies (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE cl_users ADD CONSTRAINT FK_5829B676E428AD9 FOREIGN KEY (cl_company_id)
            REFERENCES cl_companies (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE events ADD CONSTRAINT FK_5387574AD5094834 FOREIGN KEY (cl_user_id)
            REFERENCES cl_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE events ADD CONSTRAINT FK_5387574A20B6C967 FOREIGN KEY (bc_project_id)
            REFERENCES bc_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE bc_cl_users DROP CONSTRAINT FK_C90FC6DEADB08941');
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_5387574A20B6C967');
        $this->addSql('ALTER TABLE bc_projects DROP CONSTRAINT FK_CDB5C30CE572ECDB');
        $this->addSql('ALTER TABLE cl_projects DROP CONSTRAINT FK_FD304741E428AD9');
        $this->addSql('ALTER TABLE cl_users DROP CONSTRAINT FK_5829B676E428AD9');
        $this->addSql('ALTER TABLE bc_projects DROP CONSTRAINT FK_CDB5C30C8FB48F93');
        $this->addSql('ALTER TABLE oauth2_tokens DROP CONSTRAINT FK_B81EC12D5094834');
        $this->addSql('ALTER TABLE bc_cl_users DROP CONSTRAINT FK_C90FC6DED5094834');
        $this->addSql('ALTER TABLE events DROP CONSTRAINT FK_5387574AD5094834');
        $this->addSql('DROP SEQUENCE oauth2_tokens_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE bc_projects_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE bc_cl_users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE cl_users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE events_id_seq CASCADE');
        $this->addSql('DROP TABLE oauth2_tokens');
        $this->addSql('DROP TABLE bc_accounts');
        $this->addSql('DROP TABLE bc_projects');
        $this->addSql('DROP TABLE bc_cl_users');
        $this->addSql('DROP TABLE cl_companies');
        $this->addSql('DROP TABLE cl_projects');
        $this->addSql('DROP TABLE cl_users');
        $this->addSql('DROP TABLE events');
    }
}
