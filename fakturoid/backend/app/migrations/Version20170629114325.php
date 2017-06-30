<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170629114325 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE invoices_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
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
            ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95D5094834 FOREIGN KEY (cl_user_id)
                REFERENCES cl_users (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('
            ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F95C26F6427 FOREIGN KEY (fa_user_id)
                REFERENCES fa_users (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP SEQUENCE invoices_id_seq CASCADE');
        $this->addSql('DROP TABLE invoices');
    }
}
