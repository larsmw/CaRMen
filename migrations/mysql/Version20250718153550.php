<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250718153550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE project ADD winner_file_name VARCHAR(255) DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
        INSERT INTO `menu_item` (`id`,`name`,`title`,`route`,`menu`,`parent`)
            VALUES (1,'Menu Items','Menu Items','/menu/item','admin',0),
                   (2,'Home','Home','/','main',0),
                   (3,'Login','Login','/login','main',2),
                   (4,'Logout','Logout','/logout','main',2),
                   (5,'Register User','Register User','/register','admin',0),
                   (6,'Customers','List of Customers','/customer','main',0);
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE project DROP winner_file_name
        SQL);
    }
}
