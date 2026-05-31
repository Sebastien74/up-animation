<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260531155539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Composite indexes for hot front listing queries (website+position on catalog category/product, website+publicationStart on newscast).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_category_website_position ON upa_module_catalog_category (website_id, position)');
        $this->addSql('CREATE INDEX idx_product_website_position ON upa_module_catalog_product (website_id, position)');
        $this->addSql('CREATE INDEX idx_newscast_website_publication ON upa_module_newscast (website_id, publicationStart)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_category_website_position ON upa_module_catalog_category');
        $this->addSql('DROP INDEX idx_product_website_position ON upa_module_catalog_product');
        $this->addSql('DROP INDEX idx_newscast_website_publication ON upa_module_newscast');
    }
}
