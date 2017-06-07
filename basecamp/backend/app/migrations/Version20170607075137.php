<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170607075137 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE events_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
            CREATE TABLE events (
                id INT NOT NULL, 
                bc_project_id INT, 
                event INT NOT NULL, 
                data JSON NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE INDEX IDX_5387574A20B6C967 ON events (bc_project_id)');
        $this->addSql(<<<SQL
            ALTER TABLE events ADD CONSTRAINT FK_5387574A20B6C967 FOREIGN KEY (bc_project_id)
                REFERENCES bc_project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
    }

    public function down(Schema $schema)
    {
        $this->addSql('DROP SEQUENCE events_id_seq CASCADE');
        $this->addSql('DROP TABLE events');
    }
}
