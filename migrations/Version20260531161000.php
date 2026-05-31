<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename media relation shape values to ordinal words (primary -> first, secondary -> second).
 */
final class Version20260531161000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate media relation shape values masked-wrap-primary/secondary to masked-wrap-first/second.';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->shapeTables() as $table) {
            $this->addSql(sprintf("UPDATE `%s` SET shape = 'masked-wrap-first' WHERE shape = 'masked-wrap-primary'", $table));
            $this->addSql(sprintf("UPDATE `%s` SET shape = 'masked-wrap-second' WHERE shape = 'masked-wrap-secondary'", $table));
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->shapeTables() as $table) {
            $this->addSql(sprintf("UPDATE `%s` SET shape = 'masked-wrap-primary' WHERE shape = 'masked-wrap-first'", $table));
            $this->addSql(sprintf("UPDATE `%s` SET shape = 'masked-wrap-secondary' WHERE shape = 'masked-wrap-second'", $table));
        }
    }

    /**
     * @return list<string>
     */
    private function shapeTables(): array
    {
        return $this->connection->fetchFirstColumn(
            "SELECT TABLE_NAME FROM information_schema.COLUMNS
             WHERE COLUMN_NAME = 'shape'
               AND TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME LIKE '%media_relation%'"
        );
    }
}
