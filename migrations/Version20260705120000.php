<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add optional FAQ relation to catalog products (FAQPage JSON-LD on product pages).
 */
final class Version20260705120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add faq_id column to catalog product table (optional FAQ rendered on the product page).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_catalog_product ADD faq_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE upa_module_catalog_product ADD CONSTRAINT FK_72D03A2D81BEC8C2 FOREIGN KEY (faq_id) REFERENCES upa_module_faq (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_72D03A2D81BEC8C2 ON upa_module_catalog_product (faq_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_catalog_product DROP FOREIGN KEY FK_72D03A2D81BEC8C2');
        $this->addSql('DROP INDEX IDX_72D03A2D81BEC8C2 ON upa_module_catalog_product');
        $this->addSql('ALTER TABLE upa_module_catalog_product DROP faq_id');
    }
}
