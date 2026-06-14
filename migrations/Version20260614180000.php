<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add source column on seo_page_analysis (manual vs cron snapshots).
 */
final class Version20260614180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add source column on upa_seo_page_analysis (manual/cron)";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE upa_seo_page_analysis ADD source VARCHAR(20) DEFAULT 'manual' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_page_analysis DROP source');
    }
}
