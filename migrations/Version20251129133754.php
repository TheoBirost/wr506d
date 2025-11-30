<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129133754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE actor ADD CONSTRAINT FK_447556F97E9E4C8C FOREIGN KEY (photo_id) REFERENCES media_object (id)');
        $this->addSql('ALTER TABLE actor_movie CHANGE movie_id movie_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE category_movie CHANGE movie_id movie_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE director CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE media_object CHANGE id id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE movie CHANGE id id VARCHAR(255) NOT NULL, CHANGE director_id director_id VARCHAR(255) DEFAULT NULL, CHANGE image_id image_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE id id VARCHAR(255) NOT NULL, CHANGE photo photo_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6497E9E4C8C FOREIGN KEY (photo_id) REFERENCES media_object (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D6497E9E4C8C ON user (photo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category_movie CHANGE movie_id movie_id INT NOT NULL');
        $this->addSql('ALTER TABLE actor DROP FOREIGN KEY FK_447556F97E9E4C8C');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497E9E4C8C');
        $this->addSql('DROP INDEX IDX_8D93D6497E9E4C8C ON user');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE photo_id photo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE media_object CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE director CHANGE id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE actor_movie CHANGE movie_id movie_id INT NOT NULL');
        $this->addSql('ALTER TABLE movie CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE director_id director_id INT DEFAULT NULL, CHANGE image_id image_id INT DEFAULT NULL');
    }
}
