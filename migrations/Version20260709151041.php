<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260709151041 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Slider: tailles de crop des vignettes par ecran (embeddable CropSizes).';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upa_module_slider ADD cropWidthDesktop INT DEFAULT NULL, ADD cropHeightDesktop INT DEFAULT NULL, ADD cropWidthLaptop INT DEFAULT NULL, ADD cropHeightLaptop INT DEFAULT NULL, ADD cropWidthTablet INT DEFAULT NULL, ADD cropHeightTablet INT DEFAULT NULL, ADD cropWidthMobile INT DEFAULT NULL, ADD cropHeightMobile INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upa_module_slider DROP cropWidthDesktop, DROP cropHeightDesktop, DROP cropWidthLaptop, DROP cropHeightLaptop, DROP cropWidthTablet, DROP cropHeightTablet, DROP cropWidthMobile, DROP cropHeightMobile');
    }
}
