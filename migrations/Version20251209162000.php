<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251209162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename tap_charge_id to charge_id';
    }

    public function up(Schema $schema): void
    {
        // MySQL specific change, adjust if using other DB
        $this->addSql('ALTER TABLE payment CHANGE tap_charge_id charge_id VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE payment CHANGE charge_id tap_charge_id VARCHAR(255) NOT NULL');
    }
}
