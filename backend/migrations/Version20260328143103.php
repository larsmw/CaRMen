<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328143103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact ADD address_line1 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD address_line2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD postal_code VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD city VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact ADD country VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact DROP address_line1');
        $this->addSql('ALTER TABLE contact DROP address_line2');
        $this->addSql('ALTER TABLE contact DROP postal_code');
        $this->addSql('ALTER TABLE contact DROP city');
        $this->addSql('ALTER TABLE contact DROP country');
    }
}
