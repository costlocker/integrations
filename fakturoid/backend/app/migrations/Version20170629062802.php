<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170629062802 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE cl_users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('
            CREATE TABLE cl_users (
                id INT NOT NULL, 
                email VARCHAR(255) NOT NULL,
                cl_company_id INT NOT NULL,
                data JSON NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX cl_unique_user ON cl_users (email, cl_company_id)');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP SEQUENCE cl_users_id_seq CASCADE');
        $this->addSql('DROP TABLE cl_users');
    }
}
