<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220707123345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, company VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE configuration (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, color VARCHAR(255) NOT NULL, capacity VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_A5E2A5D74584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_client (customer_id INT NOT NULL, client_id INT NOT NULL, INDEX IDX_7681D8049395C3F3 (customer_id), INDEX IDX_7681D80419EB6921 (client_id), PRIMARY KEY(customer_id, client_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, configuration_id INT NOT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_C53D045F73F32DD8 (configuration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL, screen_size DOUBLE PRECISION NOT NULL, camera TINYINT(1) NOT NULL, bluetooth TINYINT(1) NOT NULL, wifi TINYINT(1) NOT NULL, length DOUBLE PRECISION NOT NULL, width DOUBLE PRECISION NOT NULL, height DOUBLE PRECISION NOT NULL, weight DOUBLE PRECISION NOT NULL, das DOUBLE PRECISION NOT NULL, manufacturer VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE configuration ADD CONSTRAINT FK_A5E2A5D74584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE customer_client ADD CONSTRAINT FK_7681D8049395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_client ADD CONSTRAINT FK_7681D80419EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F73F32DD8 FOREIGN KEY (configuration_id) REFERENCES configuration (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_client DROP FOREIGN KEY FK_7681D80419EB6921');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F73F32DD8');
        $this->addSql('ALTER TABLE customer_client DROP FOREIGN KEY FK_7681D8049395C3F3');
        $this->addSql('ALTER TABLE configuration DROP FOREIGN KEY FK_A5E2A5D74584665A');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE configuration');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE customer_client');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE product');
    }
}
