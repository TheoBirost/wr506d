<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128181615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Temporarily disable foreign key checks to allow column type changes.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE actor CHANGE photo_id photo_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE actor_movie CHANGE movie_id movie_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE category_movie CHANGE movie_id movie_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE director CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE media_object CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE movie CHANGE id id VARCHAR(255) NOT NULL, CHANGE director_id director_id VARCHAR(255) DEFAULT NULL, CHANGE image_id image_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id VARCHAR(255) NOT NULL, CHANGE photo_id photo_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('ALTER TABLE category_movie CHANGE movie_id movie_id INT NOT NULL');
        $this->addSql('ALTER TABLE actor CHANGE photo_id photo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE photo_id photo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE media_object CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE director CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE actor_movie CHANGE movie_id movie_id INT NOT NULL');
        $this->addSql('ALTER TABLE movie CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE director_id director_id INT DEFAULT NULL, CHANGE image_id image_id INT DEFAULT NULL');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
