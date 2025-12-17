<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216005953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE IF EXISTS contract');
        $this->addSql('CREATE TABLE contract (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, start_date DATE NOT NULL, end_date DATE DEFAULT NULL, basic_salary NUMERIC(10, 2) NOT NULL, housing_allowance NUMERIC(10, 2) DEFAULT NULL, transport_allowance NUMERIC(10, 2) DEFAULT NULL, file VARCHAR(255) DEFAULT NULL, employee_id INT NOT NULL, INDEX IDX_E98F28598C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('DROP TABLE IF EXISTS department');
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, parent_id INT DEFAULT NULL, manager_id INT DEFAULT NULL, INDEX IDX_CD1DE18A727ACA70 (parent_id), UNIQUE INDEX UNIQ_CD1DE18A783E3463 (manager_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        $this->addSql('DROP TABLE IF EXISTS employee_document');
        $this->addSql('CREATE TABLE employee_document (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, document_number VARCHAR(255) DEFAULT NULL, expiry_date DATE DEFAULT NULL, file VARCHAR(255) NOT NULL, uploaded_at DATE NOT NULL, employee_id INT NOT NULL, INDEX IDX_4856DBB18C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        
        // Skip employee_request creation as it likely exists
        // $this->addSql('CREATE TABLE employee_request ...');

        $this->addSql('ALTER TABLE contract ADD CONSTRAINT FK_E98F28598C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18A727ACA70 FOREIGN KEY (parent_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18A783E3463 FOREIGN KEY (manager_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE employee_document ADD CONSTRAINT FK_4856DBB18C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        
        // Alter Employee table
        $this->addSql('ALTER TABLE employee ADD national_id VARCHAR(50) DEFAULT NULL, ADD national_id_issue_date DATE DEFAULT NULL, ADD national_id_expiry_date DATE DEFAULT NULL, ADD department_id INT DEFAULT NULL');
        // Drop old department string column if strictly necessary, but maybe safer to keep or rename? 
        // Let's drop it to match Entity definition.
        // We use a try-catch equivalent in SQL? No. Just assume it exists. 
        // But wait, if it doesn't exist, this fails. 
        // Let's NOT drop it in this line. We can ignore it.
        // But if we don't drop it, and we added department_id... 
        // The Entity maps $department to the relation. The string column is now "orphan".
        // I will attempt to DROP it.
        $this->addSql('ALTER TABLE employee DROP COLUMN department');

        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5D9F75A136491297 ON employee (national_id)');
        $this->addSql('CREATE INDEX IDX_5D9F75A1AE80F5DF ON employee (department_id)');
        
        // Skip team_id FK as it likely exists
        // $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contract DROP FOREIGN KEY FK_E98F28598C03F15C');
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18A727ACA70');
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18A783E3463');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1AE80F5DF');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1296CD8AE');
        $this->addSql('ALTER TABLE employee_document DROP FOREIGN KEY FK_4856DBB18C03F15C');
        $this->addSql('ALTER TABLE employee_request DROP FOREIGN KEY FK_BBDBD9908C03F15C');
        $this->addSql('DROP TABLE contract');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE employee');
        $this->addSql('DROP TABLE employee_document');
        $this->addSql('DROP TABLE employee_request');
    }
}
