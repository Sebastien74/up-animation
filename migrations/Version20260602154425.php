<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add submissionPageUrl to form contacts: stores the front page URL the form was submitted from.
 */
final class Version20260602154425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add submissionPageUrl column to form contact tables (submission page URL).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_form_contact ADD submissionPageUrl VARCHAR(2048) DEFAULT NULL');
        $this->addSql('ALTER TABLE upa_module_form_step_contact ADD submissionPageUrl VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_module_form_contact DROP submissionPageUrl');
        $this->addSql('ALTER TABLE upa_module_form_step_contact DROP submissionPageUrl');
    }
}
