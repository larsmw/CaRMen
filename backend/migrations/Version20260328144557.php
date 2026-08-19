<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328144557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE role_permissions (role VARCHAR(50) NOT NULL, permissions JSON NOT NULL, PRIMARY KEY (role))');
        $this->addSql('ALTER TABLE "user" DROP permissions');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('ALTER TABLE "user" ADD permissions JSON DEFAULT \'[]\' NOT NULL');
    }
}
