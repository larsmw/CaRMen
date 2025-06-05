<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605142922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE permission (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, permissions_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_57698A6A9C3E4F87 (permissions_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE role ADD CONSTRAINT FK_57698A6A9C3E4F87 FOREIGN KEY (permissions_id) REFERENCES permission (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE role DROP FOREIGN KEY FK_57698A6A9C3E4F87
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE permission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE role
        SQL);
    }
}
