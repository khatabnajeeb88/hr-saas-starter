<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251221172539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY `FK_5D9F75A1296CD8AE`');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1296CD8AE');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT `FK_5D9F75A1296CD8AE` FOREIGN KEY (team_id) REFERENCES team (id)');
    }
}
