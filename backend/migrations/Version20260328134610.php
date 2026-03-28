<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328134610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA IF NOT EXISTS public');
        $this->addSql('CREATE TABLE account (id UUID NOT NULL, name VARCHAR(255) NOT NULL, industry VARCHAR(100) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, address_line1 VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, employee_count INT DEFAULT NULL, annual_revenue NUMERIC(15, 2) DEFAULT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE activity (id UUID NOT NULL, type VARCHAR(50) NOT NULL, subject VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, status VARCHAR(50) DEFAULT \'planned\' NOT NULL, scheduled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, contact_id UUID DEFAULT NULL, deal_id UUID DEFAULT NULL, assigned_to_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_AC74095AE7A1254A ON activity (contact_id)');
        $this->addSql('CREATE INDEX IDX_AC74095AF60E2305 ON activity (deal_id)');
        $this->addSql('CREATE INDEX IDX_AC74095AF4BD7827 ON activity (assigned_to_id)');
        $this->addSql('CREATE TABLE contact (id UUID NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, mobile VARCHAR(20) DEFAULT NULL, job_title VARCHAR(255) DEFAULT NULL, department VARCHAR(100) DEFAULT NULL, status VARCHAR(50) DEFAULT \'lead\' NOT NULL, notes TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, account_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4C62E638E7927C74 ON contact (email)');
        $this->addSql('CREATE INDEX IDX_4C62E6389B6B5FBA ON contact (account_id)');
        $this->addSql('CREATE TABLE deal (id UUID NOT NULL, title VARCHAR(255) NOT NULL, value NUMERIC(15, 2) NOT NULL, currency VARCHAR(3) DEFAULT \'USD\' NOT NULL, stage VARCHAR(50) NOT NULL, probability SMALLINT DEFAULT 50 NOT NULL, close_date DATE DEFAULT NULL, description TEXT DEFAULT NULL, lost_reason TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, account_id UUID DEFAULT NULL, primary_contact_id UUID DEFAULT NULL, owner_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E3FEC1169B6B5FBA ON deal (account_id)');
        $this->addSql('CREATE INDEX IDX_E3FEC116D905C92C ON deal (primary_contact_id)');
        $this->addSql('CREATE INDEX IDX_E3FEC1167E3C61F9 ON deal (owner_id)');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_active BOOLEAN DEFAULT true NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AF60E2305 FOREIGN KEY (deal_id) REFERENCES deal (id)');
        $this->addSql('ALTER TABLE activity ADD CONSTRAINT FK_AC74095AF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES "user" (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E6389B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE deal ADD CONSTRAINT FK_E3FEC1169B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE deal ADD CONSTRAINT FK_E3FEC116D905C92C FOREIGN KEY (primary_contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE deal ADD CONSTRAINT FK_E3FEC1167E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity DROP CONSTRAINT FK_AC74095AE7A1254A');
        $this->addSql('ALTER TABLE activity DROP CONSTRAINT FK_AC74095AF60E2305');
        $this->addSql('ALTER TABLE activity DROP CONSTRAINT FK_AC74095AF4BD7827');
        $this->addSql('ALTER TABLE contact DROP CONSTRAINT FK_4C62E6389B6B5FBA');
        $this->addSql('ALTER TABLE deal DROP CONSTRAINT FK_E3FEC1169B6B5FBA');
        $this->addSql('ALTER TABLE deal DROP CONSTRAINT FK_E3FEC116D905C92C');
        $this->addSql('ALTER TABLE deal DROP CONSTRAINT FK_E3FEC1167E3C61F9');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE deal');
        $this->addSql('DROP TABLE "user"');
    }
}
