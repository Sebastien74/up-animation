<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add axonautEnabled flag on form configuration to push contacts to Axonaut CRM.
 */
final class Version20260611120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add axonautEnabled column on module_form_configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_form_configuration ADD axonautEnabled TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_form_configuration DROP axonautEnabled');
    }
}
