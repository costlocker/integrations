<?php

namespace Costlocker\Integrations\Database\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170606102241 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('CREATE SEQUENCE apitoken_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
            CREATE TABLE apitoken (
                id INT NOT NULL, 
                costlocker_user_id INT NOT NULL, 
                basecamp_user_id INT DEFAULT NULL, 
                access_token TEXT NOT NULL, 
                refresh_token TEXT NOT NULL, 
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE INDEX IDX_23E5A7D359B2718D ON apitoken (costlocker_user_id)');
        $this->addSql('CREATE INDEX IDX_23E5A7D38065D4FD ON apitoken (basecamp_user_id)');
        $this->addSql(<<<SQL
            CREATE TABLE bc_account (
                id INT NOT NULL, 
                basecamp_user_id INT NOT NULL, 
                name VARCHAR(255) NOT NULL, 
                product VARCHAR(255) NOT NULL, 
                url_api VARCHAR(255) NOT NULL, 
                url_app VARCHAR(255) NOT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE INDEX IDX_3F2A69EA8065D4FD ON bc_account (basecamp_user_id)');
        $this->addSql(<<<SQL
            CREATE TABLE bc_user (
                id INT NOT NULL, 
                costlocker_user_id INT NOT NULL, 
                data JSON NOT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE SEQUENCE cl_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE INDEX IDX_19B1AE0959B2718D ON bc_user (costlocker_user_id)');
        $this->addSql(<<<SQL
            CREATE TABLE cl_user (
                id INT NOT NULL, 
                email VARCHAR(255) NOT NULL,
                id_tenant INT NOT NULL,
                data JSON NOT NULL, 
                PRIMARY KEY(id)
            )
SQL
        );
        $this->addSql('CREATE UNIQUE INDEX cl_unique_user ON cl_user (email, id_tenant)');
        $this->addSql('CREATE INDEX cl_user_tenant ON cl_user (id_tenant)');
        $this->addSql(<<<SQL
            ALTER TABLE apitoken ADD CONSTRAINT FK_23E5A7D359B2718D FOREIGN KEY (costlocker_user_id)
                REFERENCES cl_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
        $this->addSql(<<<SQL
            ALTER TABLE apitoken ADD CONSTRAINT FK_23E5A7D38065D4FD FOREIGN KEY (basecamp_user_id)
                REFERENCES bc_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
        $this->addSql(<<<SQL
            ALTER TABLE bc_account ADD CONSTRAINT FK_3F2A69EA8065D4FD FOREIGN KEY (basecamp_user_id)
                REFERENCES bc_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
        $this->addSql(<<<SQL
            ALTER TABLE bc_user ADD CONSTRAINT FK_19B1AE0959B2718D FOREIGN KEY (costlocker_user_id)
                REFERENCES cl_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
SQL
        );
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE apitoken DROP CONSTRAINT FK_23E5A7D38065D4FD');
        $this->addSql('ALTER TABLE bc_account DROP CONSTRAINT FK_3F2A69EA8065D4FD');
        $this->addSql('ALTER TABLE apitoken DROP CONSTRAINT FK_23E5A7D359B2718D');
        $this->addSql('ALTER TABLE bc_user DROP CONSTRAINT FK_19B1AE0959B2718D');
        $this->addSql('DROP SEQUENCE apitoken_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE cl_user_id_seq CASCADE');
        $this->addSql('DROP TABLE apitoken');
        $this->addSql('DROP TABLE bc_account');
        $this->addSql('DROP TABLE bc_user');
        $this->addSql('DROP TABLE cl_user');
    }
}
