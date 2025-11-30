<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129134532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change movie ID from INT to VARCHAR and add media_object/user tables';
    }

    public function up(Schema $schema): void
    {
        // 1. Create media_object table if it doesn't exist
        $this->addSql('CREATE TABLE IF NOT EXISTS media_object (
            id VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 2. Create user table if it doesn't exist
        $this->addSql('CREATE TABLE IF NOT EXISTS user (
            id VARCHAR(255) NOT NULL,
            photo_id VARCHAR(255) DEFAULT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL COMMENT \'(DC2Type:json)\',
            password VARCHAR(255) NOT NULL,
            create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            firstname VARCHAR(255) NOT NULL,
            lastname VARCHAR(255) NOT NULL,
            dob DATE DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // 3. Add indexes and constraints to user table (if not exists)
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_8D93D6497E9E4C8C ON user (photo_id)');

        // 4. Add foreign key to user table (check if not exists first)
        $this->addSql('SET @fk_exists = (
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = "user"
            AND CONSTRAINT_NAME = "FK_8D93D6497E9E4C8C"
        )');
        $this->addSql('SET @sql = IF(@fk_exists = 0,
            "ALTER TABLE user ADD CONSTRAINT FK_8D93D6497E9E4C8C FOREIGN KEY (photo_id) REFERENCES media_object (id) ON DELETE SET NULL",
            "SELECT 1"
        )');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // 5. Drop ALL foreign keys that reference movie.id
        $this->addSql('SET @fk_list = (
            SELECT GROUP_CONCAT(CONCAT("ALTER TABLE ", TABLE_NAME, " DROP FOREIGN KEY ", CONSTRAINT_NAME) SEPARATOR "; ")
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME = "movie"
            AND REFERENCED_COLUMN_NAME = "id"
        )');
        $this->addSql('SET @sql = IF(@fk_list IS NOT NULL, @fk_list, "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // 6. Drop ALL foreign keys from movie table
        $this->addSql('SET @fk_list = (
            SELECT GROUP_CONCAT(CONCAT("ALTER TABLE movie DROP FOREIGN KEY ", CONSTRAINT_NAME) SEPARATOR "; ")
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = "movie"
            AND CONSTRAINT_TYPE = "FOREIGN KEY"
        )');
        $this->addSql('SET @sql = IF(@fk_list IS NOT NULL, @fk_list, "SELECT 1")');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // 7. Modify movie_id columns to VARCHAR in related tables
        $this->addSql('ALTER TABLE actor_movie MODIFY movie_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE category_movie MODIFY movie_id VARCHAR(255) NOT NULL');

        // 8. Modify director and movie tables
        $this->addSql('ALTER TABLE director MODIFY id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE movie MODIFY id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE movie MODIFY director_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE movie CHANGE image image_id VARCHAR(255) DEFAULT NULL');

        // 9. Recreate foreign keys
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_1D5EF26F3DA5256D ON movie (image_id)');
        $this->addSql('SET @fk_exists = (
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = "movie"
            AND CONSTRAINT_NAME = "FK_1D5EF26F3DA5256D"
        )');
        $this->addSql('SET @sql = IF(@fk_exists = 0,
            "ALTER TABLE movie ADD CONSTRAINT FK_1D5EF26F3DA5256D FOREIGN KEY (image_id) REFERENCES media_object (id)",
            "SELECT 1"
        )');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // 10. Recreate foreign key from movie to director
        $this->addSql('SET @fk_exists = (
            SELECT COUNT(*)
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = "movie"
            AND CONSTRAINT_NAME = "FK_1D5EF26F899FB366"
        )');
        $this->addSql('SET @sql = IF(@fk_exists = 0,
            "ALTER TABLE movie ADD CONSTRAINT FK_1D5EF26F899FB366 FOREIGN KEY (director_id) REFERENCES director (id)",
            "SELECT 1"
        )');
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // 11. Recreate foreign keys on actor_movie and category_movie
        $this->addSql('ALTER TABLE actor_movie ADD CONSTRAINT FK_39DA19FB8F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_movie ADD CONSTRAINT FK_D5436F078F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Reverse all changes
        $this->addSql('ALTER TABLE actor_movie DROP FOREIGN KEY FK_39DA19FB8F93B6FC');
        $this->addSql('ALTER TABLE category_movie DROP FOREIGN KEY FK_D5436F078F93B6FC');
        $this->addSql('ALTER TABLE movie DROP FOREIGN KEY FK_1D5EF26F899FB366');
        $this->addSql('ALTER TABLE movie DROP FOREIGN KEY FK_1D5EF26F3DA5256D');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6497E9E4C8C');

        $this->addSql('DROP TABLE IF EXISTS user');
        $this->addSql('DROP TABLE IF EXISTS media_object');

        $this->addSql('ALTER TABLE category_movie MODIFY movie_id INT NOT NULL');
        $this->addSql('ALTER TABLE actor_movie MODIFY movie_id INT NOT NULL');
        $this->addSql('ALTER TABLE director MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX IDX_1D5EF26F3DA5256D ON movie');
        $this->addSql('ALTER TABLE movie MODIFY id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE movie MODIFY director_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE movie CHANGE image_id image VARCHAR(255) DEFAULT NULL');

        // Recreate original foreign keys
        $this->addSql('ALTER TABLE movie ADD CONSTRAINT FK_1D5EF26F899FB366 FOREIGN KEY (director_id) REFERENCES director (id)');
        $this->addSql('ALTER TABLE actor_movie ADD CONSTRAINT FK_39DA19FB8F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE category_movie ADD CONSTRAINT FK_D5436F078F93B6FC FOREIGN KEY (movie_id) REFERENCES movie (id) ON DELETE CASCADE');
    }
}
