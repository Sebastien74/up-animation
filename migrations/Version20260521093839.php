<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521093839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add disabledAuth kill-switch column on admin User to bypass 2FA entirely for a given account.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_security_user ADD disabledAuth TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_security_user DROP disabledAuth');
    }
}
