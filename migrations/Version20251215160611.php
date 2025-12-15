<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215160611 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE employee_contract (id INT AUTO_INCREMENT NOT NULL, contract_type VARCHAR(50) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, wage_type VARCHAR(50) NOT NULL, wage_amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, employee_id INT NOT NULL, INDEX IDX_79B0799E8C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE employee_request (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, data JSON NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, employee_id INT NOT NULL, INDEX IDX_BBDBD9908C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE employee_contract ADD CONSTRAINT FK_79B0799E8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE employee_request ADD CONSTRAINT FK_BBDBD9908C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE employee ADD mobile VARCHAR(20) DEFAULT NULL, ADD gender VARCHAR(10) DEFAULT NULL, ADD date_of_birth DATE DEFAULT NULL, ADD address LONGTEXT DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD bank_name VARCHAR(255) DEFAULT NULL, ADD bank_identifier_code VARCHAR(50) DEFAULT NULL, ADD bank_branch VARCHAR(100) DEFAULT NULL, ADD bank_account_number VARCHAR(50) DEFAULT NULL, ADD work_type VARCHAR(50) DEFAULT NULL, ADD shift VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee_contract DROP FOREIGN KEY FK_79B0799E8C03F15C');
        $this->addSql('ALTER TABLE employee_request DROP FOREIGN KEY FK_BBDBD9908C03F15C');
        $this->addSql('DROP TABLE employee_contract');
        $this->addSql('DROP TABLE employee_request');
        $this->addSql('ALTER TABLE employee DROP mobile, DROP gender, DROP date_of_birth, DROP address, DROP city, DROP country, DROP bank_name, DROP bank_identifier_code, DROP bank_branch, DROP bank_account_number, DROP work_type, DROP shift');
    }
}
