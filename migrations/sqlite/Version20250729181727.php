<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250729181727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT, address_line1 VARCHAR(255) NOT NULL, address_line2 VARCHAR(255) DEFAULT NULL, postalcode VARCHAR(12) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE customer (id INTEGER PRIMARY KEY AUTOINCREMENT, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(16) NOT NULL, mail VARCHAR(1024) DEFAULT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255) NOT NULL, machine_name VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE menu_item (id INTEGER PRIMARY KEY AUTOINCREMENT, parent INT NOT NULL, name VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, route VARCHAR(255) NOT NULL, menu VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE note (id INTEGER PRIMARY KEY AUTOINCREMENT, note_value VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE permission (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255) NOT NULL, action VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE project (id INTEGER PRIMARY KEY AUTOINCREMENT, customer_id INT NOT NULL, winner_file VARCHAR(255) DEFAULT NULL, winner_file_name VARCHAR(255) DEFAULT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reset_password_request (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL , expires_at DATETIME NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE role (id INTEGER PRIMARY KEY AUTOINCREMENT, permissions_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT, username VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE9395C3F3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE role DROP FOREIGN KEY FK_57698A6A9C3E4F87
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE address
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE customer
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE menu
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE menu_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE note
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE permission
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE project
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reset_password_request
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE role
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
