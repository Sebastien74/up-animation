<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add report JSON column on seo_page_analysis to store the full analysis result.
 */
final class Version20260613160600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add report JSON column on upa_seo_page_analysis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_page_analysis ADD report JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_page_analysis DROP report');
    }
}
