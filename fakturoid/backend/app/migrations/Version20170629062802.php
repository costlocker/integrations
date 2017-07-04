<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170629062802 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE cl_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE fa_accounts_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE fa_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE invoices_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('
            CREATE TABLE cl_users (
                id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                cl_company_id INT NOT NULL,
                data JSON NOT NULL,
                fa_user_id INT,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_5829B676C26F6427 ON cl_users (fa_user_id)');
        $this->addSql('CREATE UNIQUE INDEX cl_unique_user ON cl_users (email, cl_company_id)');
        $this->addSql('
            CREATE TABLE fa_accounts (
                id INT NOT NULL,
                slug VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                subjects JSON NOT NULL,
                subjects_downloaded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('
            CREATE TABLE fa_users (
                id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                data JSON NOT NULL,
                fa_user_id INT NOT NULL,
                fa_company_id INT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_711ED9D28DC23824 ON fa_users (fa_company_id)');
        $this->addSql('
            CREATE TABLE invoices (
                id INT NOT NULL, 
                cl_user_id INT NOT NULL, 
                fa_user_id INT NOT NULL, 
                cl_invoice_id INT NOT NULL, 
                fa_invoice_id INT NOT NULL, 
                fa_invoice_number VARCHAR(255) NOT NULL, 
                cl_project_id INT NOT NULL, 
                cl_client_id INT NOT NULL, 
                fa_subject_id INT NOT NULL,
                data JSON NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE INDEX IDX_6A2F2F95D5094834 ON invoices (cl_user_id)');
        $this->addSql('CREATE INDEX IDX_6A2F2F95C26F6427 ON invoices (fa_user_id)');
        $this->addSql('CREATE INDEX invoices_cl_project_id_idx ON invoices (cl_project_id)');
        $this->addSql('CREATE INDEX invoices_cl_client_id_idx ON invoices (cl_client_id)');
        $this->addSql('CREATE INDEX invoices_fa_subject_id_idx ON invoices (fa_subject_id)');
        $this->addSql('
            ALTER TABLE cl_users ADD CONSTRAINT FK_5829B676C26F6427 FOREIGN KEY (fa_user_id) 
                REFERENCES fa_users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('
            ALTER TABLE fa_users ADD CONSTRAINT FK_711ED9D28DC23824 FOREIGN KEY (fa_company_id)
                REFERENCES fa_accounts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('
            ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95D5094834 FOREIGN KEY (cl_user_id)
                REFERENCES cl_users (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('
            ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95C26F6427 FOREIGN KEY (fa_user_id)
                REFERENCES fa_users (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE invoices DROP CONSTRAINT FK_6A2F2F95D5094834');
        $this->addSql('ALTER TABLE fa_users DROP CONSTRAINT FK_711ED9D28DC23824');
        $this->addSql('ALTER TABLE cl_users DROP CONSTRAINT FK_5829B676C26F6427');
        $this->addSql('ALTER TABLE invoices DROP CONSTRAINT FK_6A2F2F95C26F6427');
        $this->addSql('DROP SEQUENCE cl_users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE fa_accounts_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE fa_users_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE invoices_id_seq CASCADE');
        $this->addSql('DROP TABLE cl_users');
        $this->addSql('DROP TABLE fa_accounts');
        $this->addSql('DROP TABLE fa_users');
        $this->addSql('DROP TABLE invoices');
    }
}
