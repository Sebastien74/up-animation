<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Track TikTok refresh token and token expiries so app:tiktok:refresh-token
 * can renew the 24 h access token before it lapses.
 */
final class Version20260529065925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add refreshToken and token expiries to api_tiktok for token refresh scheduling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_api_tiktok ADD refreshToken VARCHAR(255) DEFAULT NULL, ADD tokenExpiresAt DATETIME DEFAULT NULL, ADD refreshTokenExpiresAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_api_tiktok DROP refreshToken, DROP tokenExpiresAt, DROP refreshTokenExpiresAt');
    }
}
