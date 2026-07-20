<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260719120831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Table de jointure Newscast <-> Product (agences) pour les variantes %location%.
        $this->addSql('CREATE TABLE upa_module_newscast_agencies (newscast_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_158518B6522FC0F8 (newscast_id), INDEX IDX_158518B64584665A (product_id), PRIMARY KEY (newscast_id, product_id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE upa_module_newscast_agencies ADD CONSTRAINT FK_158518B6522FC0F8 FOREIGN KEY (newscast_id) REFERENCES upa_module_newscast (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE upa_module_newscast_agencies ADD CONSTRAINT FK_158518B64584665A FOREIGN KEY (product_id) REFERENCES upa_module_catalog_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_newscast_agencies DROP FOREIGN KEY FK_158518B6522FC0F8');
        $this->addSql('ALTER TABLE upa_module_newscast_agencies DROP FOREIGN KEY FK_158518B64584665A');
        $this->addSql('DROP TABLE upa_module_newscast_agencies');
    }
}
