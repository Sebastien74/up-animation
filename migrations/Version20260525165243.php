<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration;

/**
 * Analytics module: raw events table (30-day retention via batched delete)
 * plus hourly aggregates (12 months) and daily aggregates (kept indefinitely).
 *
 * Retention is enforced by the app:analytics:purge nightly command.
 */
final class Version20260525165243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Analytics module: create event, hourly and daily aggregate tables.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            throw new AbortMigration('Analytics module currently targets MySQL or MariaDB.');
        }

        $this->addSql('CREATE TABLE upa_analytics_event ('
            .'id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, '
            .'websiteId INT NOT NULL, '
            .'occurredAt DATETIME(3) NOT NULL, '
            .'sessionHash VARCHAR(32) NOT NULL, '
            .'eventType VARCHAR(16) NOT NULL, '
            .'urlPath VARCHAR(512) NOT NULL, '
            .'referrerDomain VARCHAR(190) DEFAULT NULL, '
            .'countryCode CHAR(2) DEFAULT NULL, '
            .'device VARCHAR(16) DEFAULT NULL, '
            .'browser VARCHAR(32) DEFAULT NULL, '
            .'os VARCHAR(32) DEFAULT NULL, '
            .'locale VARCHAR(8) DEFAULT NULL, '
            .'viewport VARCHAR(16) DEFAULT NULL, '
            .'eventPayload JSON DEFAULT NULL, '
            .'INDEX idx_analytics_event_site_time (websiteId, occurredAt), '
            .'INDEX idx_analytics_event_session (sessionHash), '
            .'INDEX idx_analytics_event_purge (occurredAt), '
            .'PRIMARY KEY (id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE upa_analytics_hourly ('
            .'id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, '
            .'websiteId INT NOT NULL, '
            .'bucketAt DATETIME NOT NULL, '
            .'urlPath VARCHAR(512) NOT NULL, '
            .'countryCode CHAR(2) DEFAULT NULL, '
            .'device VARCHAR(16) DEFAULT NULL, '
            .'visitors INT UNSIGNED NOT NULL DEFAULT 0, '
            .'sessions INT UNSIGNED NOT NULL DEFAULT 0, '
            .'pageviews INT UNSIGNED NOT NULL DEFAULT 0, '
            .'bounces INT UNSIGNED NOT NULL DEFAULT 0, '
            .'durationSum INT UNSIGNED NOT NULL DEFAULT 0, '
            .'UNIQUE INDEX uniq_analytics_hourly_dim (websiteId, bucketAt, urlPath, countryCode, device), '
            .'INDEX idx_analytics_hourly_site_time (websiteId, bucketAt), '
            .'PRIMARY KEY (id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE upa_analytics_daily ('
            .'id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, '
            .'websiteId INT NOT NULL, '
            .'bucketDate DATE NOT NULL, '
            .'urlPath VARCHAR(512) NOT NULL, '
            .'countryCode CHAR(2) DEFAULT NULL, '
            .'device VARCHAR(16) DEFAULT NULL, '
            .'visitors INT UNSIGNED NOT NULL DEFAULT 0, '
            .'sessions INT UNSIGNED NOT NULL DEFAULT 0, '
            .'pageviews INT UNSIGNED NOT NULL DEFAULT 0, '
            .'bounces INT UNSIGNED NOT NULL DEFAULT 0, '
            .'durationSum INT UNSIGNED NOT NULL DEFAULT 0, '
            .'UNIQUE INDEX uniq_analytics_daily_dim (websiteId, bucketDate, urlPath, countryCode, device), '
            .'INDEX idx_analytics_daily_site_date (websiteId, bucketDate), '
            .'PRIMARY KEY (id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS upa_analytics_daily');
        $this->addSql('DROP TABLE IF EXISTS upa_analytics_hourly');
        $this->addSql('DROP TABLE IF EXISTS upa_analytics_event');
    }
}
