<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop upa_seo_page_analysis table: the internal CMS page analyzer is removed, only
 * Google PageSpeed Insights snapshots are kept (upa_seo_page_speed_snapshot).
 */
final class Version20260618120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop upa_seo_page_analysis table (internal page analyzer removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_page_analysis DROP FOREIGN KEY FK_seo_page_analysis_website');
        $this->addSql('DROP TABLE upa_seo_page_analysis');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE upa_seo_page_analysis (id INT AUTO_INCREMENT NOT NULL, urlCode VARCHAR(255) DEFAULT NULL, locale VARCHAR(10) DEFAULT NULL, source VARCHAR(20) DEFAULT \'manual\' NOT NULL, score INT DEFAULT NULL, htmlKb INT NOT NULL, domCount INT NOT NULL, imagesCount INT NOT NULL, requests INT NOT NULL, renderMs INT DEFAULT NULL, externalDomains INT NOT NULL, severityHigh INT NOT NULL, severityMedium INT NOT NULL, severityLow INT NOT NULL, httpStatus INT DEFAULT NULL, report JSON DEFAULT NULL, createdAt DATETIME DEFAULT NULL, website_id INT DEFAULT NULL, INDEX idx_seo_page_analysis_lookup (website_id, urlCode, locale), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE upa_seo_page_analysis ADD CONSTRAINT FK_seo_page_analysis_website FOREIGN KEY (website_id) REFERENCES upa_core_website (id) ON DELETE CASCADE');
    }
}
