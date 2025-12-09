<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209155500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gateway column to payment table';
    }

    public function up(Schema $schema): void
    {
        // Check platform to ensure compatibility if needed, but standard SQL usually works
        $this->addSql("ALTER TABLE payment ADD gateway VARCHAR(50) DEFAULT 'tap' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment DROP gateway');
    }
}
