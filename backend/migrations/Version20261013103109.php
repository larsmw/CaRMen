<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261013103109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'API keys for integrations (lead intake)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE api_key (id UUID NOT NULL, name VARCHAR(100) NOT NULL, prefix VARCHAR(16) NOT NULL, key_hash VARCHAR(64) NOT NULL, scopes JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_by_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C912ED9D93B1868E ON api_key (prefix)');
        $this->addSql('CREATE INDEX IDX_C912ED9DB03A8386 ON api_key (created_by_id)');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_C912ED9DB03A8386 FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_key DROP CONSTRAINT FK_C912ED9DB03A8386');
        $this->addSql('DROP TABLE api_key');
    }
}
