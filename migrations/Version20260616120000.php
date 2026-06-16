<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create seo_pagespeed_snapshot table to historize Google PageSpeed Insights results.
 */
final class Version20260616120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create upa_seo_pagespeed_snapshot table (PageSpeed Insights history snapshots)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE upa_seo_pagespeed_snapshot (id INT AUTO_INCREMENT NOT NULL, urlCode VARCHAR(255) DEFAULT NULL, locale VARCHAR(10) DEFAULT NULL, perfMobile INT DEFAULT NULL, perfDesktop INT DEFAULT NULL, accessibility INT DEFAULT NULL, bestPractices INT DEFAULT NULL, seo INT DEFAULT NULL, lcpMs INT DEFAULT NULL, tbtMs INT DEFAULT NULL, clsX1000 INT DEFAULT NULL, fieldData TINYINT(1) NOT NULL, report JSON DEFAULT NULL, createdAt DATETIME DEFAULT NULL, website_id INT DEFAULT NULL, INDEX idx_seo_pagespeed_lookup (website_id, urlCode, locale), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE upa_seo_pagespeed_snapshot ADD CONSTRAINT FK_seo_pagespeed_website FOREIGN KEY (website_id) REFERENCES upa_core_website (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_pagespeed_snapshot DROP FOREIGN KEY FK_seo_pagespeed_website');
        $this->addSql('DROP TABLE upa_seo_pagespeed_snapshot');
    }
}
