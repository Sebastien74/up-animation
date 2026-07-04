<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add referer to form contacts: stores the raw HTTP Referer header of the submission request.
 */
final class Version20260701120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add referer column to form contact tables (raw HTTP referer of the submission).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_form_contact ADD referer VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE upa_module_form_step_contact ADD referer VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_form_contact DROP referer');
        $this->addSql('ALTER TABLE upa_module_form_step_contact DROP referer');
    }
}
