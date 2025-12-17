<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251217021138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE employee_employee_tag (employee_id INT NOT NULL, employee_tag_id INT NOT NULL, INDEX IDX_2439EA7E8C03F15C (employee_id), INDEX IDX_2439EA7E93E3A784 (employee_tag_id), PRIMARY KEY (employee_id, employee_tag_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE employee_tag (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE employment_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE family_member (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, relationship VARCHAR(50) NOT NULL, date_of_birth DATE DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, employee_id INT NOT NULL, INDEX IDX_B9D4AD6D8C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE employee_employee_tag ADD CONSTRAINT FK_2439EA7E8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE employee_employee_tag ADD CONSTRAINT FK_2439EA7E93E3A784 FOREIGN KEY (employee_tag_id) REFERENCES employee_tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE family_member ADD CONSTRAINT FK_B9D4AD6D8C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE employee ADD badge_id VARCHAR(50) DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, ADD marital_status VARCHAR(50) DEFAULT NULL, ADD employee_role VARCHAR(255) DEFAULT NULL, ADD work_location VARCHAR(255) DEFAULT NULL, ADD work_email VARCHAR(255) DEFAULT NULL, ADD work_phone VARCHAR(20) DEFAULT NULL, ADD joining_date DATE DEFAULT NULL, ADD contract_end_date DATE DEFAULT NULL, ADD basic_salary NUMERIC(10, 2) DEFAULT NULL, ADD iban VARCHAR(50) DEFAULT NULL, ADD manager_id INT DEFAULT NULL, ADD employment_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1783E3463 FOREIGN KEY (manager_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A11BCDC34A FOREIGN KEY (employment_type_id) REFERENCES employment_type (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5D9F75A1F7A2C2FC ON employee (badge_id)');
        $this->addSql('CREATE INDEX IDX_5D9F75A1783E3463 ON employee (manager_id)');
        $this->addSql('CREATE INDEX IDX_5D9F75A11BCDC34A ON employee (employment_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee_employee_tag DROP FOREIGN KEY FK_2439EA7E8C03F15C');
        $this->addSql('ALTER TABLE employee_employee_tag DROP FOREIGN KEY FK_2439EA7E93E3A784');
        $this->addSql('ALTER TABLE family_member DROP FOREIGN KEY FK_B9D4AD6D8C03F15C');
        $this->addSql('DROP TABLE employee_employee_tag');
        $this->addSql('DROP TABLE employee_tag');
        $this->addSql('DROP TABLE employment_type');
        $this->addSql('DROP TABLE family_member');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1783E3463');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A11BCDC34A');
        $this->addSql('DROP INDEX UNIQ_5D9F75A1F7A2C2FC ON employee');
        $this->addSql('DROP INDEX IDX_5D9F75A1783E3463 ON employee');
        $this->addSql('DROP INDEX IDX_5D9F75A11BCDC34A ON employee');
        $this->addSql('ALTER TABLE employee DROP badge_id, DROP experience, DROP marital_status, DROP employee_role, DROP work_location, DROP work_email, DROP work_phone, DROP joining_date, DROP contract_end_date, DROP basic_salary, DROP iban, DROP manager_id, DROP employment_type_id');
    }
}
