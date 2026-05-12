<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create api_feed_post table to persist Instagram/TikTok feeds locally';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE api_feed_post (
                id INT AUTO_INCREMENT NOT NULL,
                provider VARCHAR(32) NOT NULL,
                external_id VARCHAR(255) NOT NULL,
                permalink VARCHAR(500) DEFAULT NULL,
                caption LONGTEXT DEFAULT NULL,
                media_type VARCHAR(32) DEFAULT NULL,
                media_local_path VARCHAR(500) DEFAULT NULL,
                thumbnail_local_path VARCHAR(500) DEFAULT NULL,
                duration INT DEFAULT NULL,
                published_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                removed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                synced_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                payload JSON DEFAULT NULL,
                UNIQUE INDEX uniq_feed_post_provider_external (provider, external_id),
                INDEX idx_feed_post_provider_removed_published (provider, removed_at, published_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE api_feed_post');
    }
}
