<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add httpStatus column on upa_seo_page_analysis (flags pages that returned an error).
 */
final class Version20260615090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add httpStatus column on upa_seo_page_analysis (HTTP error flag)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_page_analysis ADD httpStatus INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_seo_page_analysis DROP httpStatus');
    }
}
