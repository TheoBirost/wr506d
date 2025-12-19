<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219095551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_two_factor (id INT AUTO_INCREMENT NOT NULL, secret VARCHAR(255) DEFAULT NULL, enabled TINYINT(1) NOT NULL, backup_codes JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user ADD two_factor_auth_id INT DEFAULT NULL, DROP two_factor_secret, DROP two_factor_enabled, DROP two_factor_backup_codes');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649555BA0C2 FOREIGN KEY (two_factor_auth_id) REFERENCES user_two_factor (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649555BA0C2 ON user (two_factor_auth_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649555BA0C2');
        $this->addSql('DROP TABLE user_two_factor');
        $this->addSql('DROP INDEX UNIQ_8D93D649555BA0C2 ON user');
        $this->addSql('ALTER TABLE user ADD two_factor_secret VARCHAR(255) DEFAULT NULL, ADD two_factor_enabled TINYINT(1) NOT NULL, ADD two_factor_backup_codes JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', DROP two_factor_auth_id');
    }
}
