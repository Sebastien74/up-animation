<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519120809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE upa_core_mail_log (createdAt DATETIME DEFAULT NULL, updatedAt DATETIME DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, fromEmail VARCHAR(255) DEFAULT NULL, fromName VARCHAR(255) DEFAULT NULL, toEmails JSON NOT NULL, ccEmails JSON DEFAULT NULL, replyTo VARCHAR(255) DEFAULT NULL, subject VARCHAR(500) DEFAULT NULL, htmlBody LONGTEXT DEFAULT NULL, textBody LONGTEXT DEFAULT NULL, attachments JSON DEFAULT NULL, template VARCHAR(255) DEFAULT NULL, locale VARCHAR(10) DEFAULT NULL, messageId VARCHAR(255) DEFAULT NULL, errorMessage LONGTEXT DEFAULT NULL, createdBy_id INT DEFAULT NULL, updatedBy_id INT DEFAULT NULL, INDEX IDX_2F5BF6B53174800F (createdBy_id), INDEX IDX_2F5BF6B565FF1AEC (updatedBy_id), INDEX idx_mail_log_created_at (createdAt), INDEX idx_mail_log_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE upa_core_mail_log ADD CONSTRAINT FK_2F5BF6B53174800F FOREIGN KEY (createdBy_id) REFERENCES upa_security_user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE upa_core_mail_log ADD CONSTRAINT FK_2F5BF6B565FF1AEC FOREIGN KEY (updatedBy_id) REFERENCES upa_security_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upa_core_mail_log DROP FOREIGN KEY FK_2F5BF6B53174800F');
        $this->addSql('ALTER TABLE upa_core_mail_log DROP FOREIGN KEY FK_2F5BF6B565FF1AEC');
        $this->addSql('DROP TABLE upa_core_mail_log');
    }
}
