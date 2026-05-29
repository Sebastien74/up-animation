<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Track Instagram long-lived token expiry so app:instagram:refresh-token
 * can renew tokens before they lapse.
 */
final class Version20260529064634 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tokenExpiresAt to api_instagram for token refresh scheduling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_api_instagram ADD tokenExpiresAt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_api_instagram DROP tokenExpiresAt');
    }
}
