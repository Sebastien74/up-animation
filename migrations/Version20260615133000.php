<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add marginsBackup column on layout zone/col/block (snapshot before margins standardization).
 */
final class Version20260615133000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add marginsBackup column on layout zone/col/block (undo of margins standardization)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_layout_zone ADD marginsBackup JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE upa_layout_col ADD marginsBackup JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE upa_layout_block ADD marginsBackup JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE upa_layout_zone DROP marginsBackup');
        $this->addSql('ALTER TABLE upa_layout_col DROP marginsBackup');
        $this->addSql('ALTER TABLE upa_layout_block DROP marginsBackup');
    }
}
