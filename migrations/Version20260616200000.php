<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create upa_seo_pagespeed_quota table to track daily PageSpeed Insights API usage.
 */
final class Version20260616200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create upa_seo_pagespeed_quota table (daily PageSpeed Insights API request counter)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE upa_seo_pagespeed_quota (id INT AUTO_INCREMENT NOT NULL, day DATE NOT NULL, count INT NOT NULL, UNIQUE INDEX uniq_seo_pagespeed_quota_day (day), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE upa_seo_pagespeed_quota');
    }
}
