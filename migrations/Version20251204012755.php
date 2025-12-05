<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204012755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, invoice_number VARCHAR(50) NOT NULL, billing_name VARCHAR(255) NOT NULL, billing_email VARCHAR(255) NOT NULL, billing_address LONGTEXT DEFAULT NULL, tax_id VARCHAR(50) DEFAULT NULL, line_items JSON NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, tax NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, issued_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, payment_id INT NOT NULL, UNIQUE INDEX UNIQ_906517442DA68207 (invoice_number), INDEX IDX_906517444C3A3BB (payment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, data JSON NOT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, tenant_id INT DEFAULT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CA9033212A (tenant_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, tap_charge_id VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, status VARCHAR(50) NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, card_last_four VARCHAR(4) DEFAULT NULL, card_brand VARCHAR(50) DEFAULT NULL, gateway_reference VARCHAR(255) DEFAULT NULL, payment_reference VARCHAR(255) DEFAULT NULL, gateway_response JSON DEFAULT NULL, failure_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subscription_id INT NOT NULL, INDEX IDX_6D28840D9A1887DC (subscription_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plan_feature (id INT AUTO_INCREMENT NOT NULL, value LONGTEXT DEFAULT NULL, enabled TINYINT NOT NULL, created_at DATETIME NOT NULL, plan_id INT NOT NULL, feature_id INT NOT NULL, INDEX IDX_A1683D6EE899029B (plan_id), INDEX IDX_A1683D6E60E4B879 (feature_id), UNIQUE INDEX UNIQ_PLAN_FEATURE (plan_id, feature_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(50) NOT NULL, trial_ends_at DATETIME DEFAULT NULL, current_period_start DATETIME DEFAULT NULL, current_period_end DATETIME DEFAULT NULL, canceled_at DATETIME DEFAULT NULL, ends_at DATETIME DEFAULT NULL, auto_renew TINYINT NOT NULL, tap_customer_id VARCHAR(255) DEFAULT NULL, payment_method_id VARCHAR(255) DEFAULT NULL, last_payment_at DATETIME DEFAULT NULL, next_billing_date DATETIME DEFAULT NULL, grace_period_ends_at DATETIME DEFAULT NULL, retry_count INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, team_id INT NOT NULL, plan_id INT NOT NULL, UNIQUE INDEX UNIQ_A3C664D3296CD8AE (team_id), INDEX IDX_A3C664D3E899029B (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscription_feature (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, feature_type VARCHAR(50) NOT NULL, default_value LONGTEXT DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_FEATURE_SLUG (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE subscription_plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, team_member_limit INT DEFAULT NULL, is_active TINYINT NOT NULL, display_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_PLAN_SLUG (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_C4E0A61F7E3C61F9 (owner_id), UNIQUE INDEX UNIQ_TEAM_SLUG (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(50) NOT NULL, joined_at DATETIME NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6FFBDA1296CD8AE (team_id), INDEX IDX_6FFBDA1A76ED395 (user_id), UNIQUE INDEX UNIQ_TEAM_USER (team_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, name VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517444C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA9033212A FOREIGN KEY (tenant_id) REFERENCES team (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_feature ADD CONSTRAINT FK_A1683D6EE899029B FOREIGN KEY (plan_id) REFERENCES subscription_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plan_feature ADD CONSTRAINT FK_A1683D6E60E4B879 FOREIGN KEY (feature_id) REFERENCES subscription_feature (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3E899029B FOREIGN KEY (plan_id) REFERENCES subscription_plan (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517444C3A3BB');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA9033212A');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D9A1887DC');
        $this->addSql('ALTER TABLE plan_feature DROP FOREIGN KEY FK_A1683D6EE899029B');
        $this->addSql('ALTER TABLE plan_feature DROP FOREIGN KEY FK_A1683D6E60E4B879');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3296CD8AE');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3E899029B');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F7E3C61F9');
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_6FFBDA1296CD8AE');
        $this->addSql('ALTER TABLE team_member DROP FOREIGN KEY FK_6FFBDA1A76ED395');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE plan_feature');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE subscription_feature');
        $this->addSql('DROP TABLE subscription_plan');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_member');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
