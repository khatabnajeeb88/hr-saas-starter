<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gateway column to subscription table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE subscription ADD gateway VARCHAR(50) DEFAULT 'tap' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription DROP gateway');
    }
}
