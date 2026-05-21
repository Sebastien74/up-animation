<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520070928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add 2FA columns: TOTP secret + backup codes + email auth flag/code on admin User; email auth flag/code on UserFront.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE upa_security_user ADD googleAuthenticatorSecret VARCHAR(255) DEFAULT NULL, ADD backupCodes JSON NOT NULL, ADD emailAuthEnabled TINYINT(1) NOT NULL DEFAULT 0, ADD emailAuthCode VARCHAR(10) DEFAULT NULL");
        $this->addSql('ALTER TABLE upa_security_user_front ADD emailAuthEnabled TINYINT(1) NOT NULL DEFAULT 0, ADD emailAuthCode VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_security_user DROP googleAuthenticatorSecret, DROP backupCodes, DROP emailAuthEnabled, DROP emailAuthCode');
        $this->addSql('ALTER TABLE upa_security_user_front DROP emailAuthEnabled, DROP emailAuthCode');
    }
}
