<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209142754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE announcement (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, start_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE announcement_read_users (announcement_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_79FF7A30913AEA17 (announcement_id), INDEX IDX_79FF7A30A76ED395 (user_id), PRIMARY KEY (announcement_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE announcement_read_users ADD CONSTRAINT FK_79FF7A30913AEA17 FOREIGN KEY (announcement_id) REFERENCES announcement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE announcement_read_users ADD CONSTRAINT FK_79FF7A30A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE announcement_read_users DROP FOREIGN KEY FK_79FF7A30913AEA17');
        $this->addSql('ALTER TABLE announcement_read_users DROP FOREIGN KEY FK_79FF7A30A76ED395');
        $this->addSql('DROP TABLE announcement');
        $this->addSql('DROP TABLE announcement_read_users');
    }
}
