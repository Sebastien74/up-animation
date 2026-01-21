<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Layout\BlockType;
use App\Entity\Security\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * BlockTypeFixtures.
 *
 * BlockType Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BlockTypeFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private int $position = 1;

    protected function loadData(ObjectManager $manager): void
    {
        $formBlocks = $this->getFormBocks();
        $layoutBlocks = $this->getLayoutBocks();
        $contentBlocks = $this->getContentBlocks();
        $blocks = array_merge($formBlocks, $layoutBlocks, $contentBlocks);

        foreach ($blocks as $config) {
            $blockType = $this->addBlockType($config);
            $this->addReference($blockType->getSlug(), $blockType);
        }
    }

    /**
     * Generate BlockType.
     */
    private function addBlockType(array $config): BlockType
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        $blockType = new BlockType();
        $blockType->setAdminName($config[0])
            ->setSlug($config[1])
            ->setCategory($config[2])
            ->setIconClass($config[3])
            ->setDropdown(!empty($config[4]))
            ->setEditable(!isset($config[5]))
            ->setPosition($this->position)
            ->setCreatedBy($user);

        if (!empty($config[5])) {
            $blockType->setFieldType(strval($config[5]));
        }

        if (!empty($config[6])) {
            $blockType->setRole($config[6]);
        }

        ++$this->position;
        $this->manager->persist($blockType);
        $this->manager->flush();

        return $blockType;
    }

    /**
     * Get BlockTypes config.
     */
    private function getFormBocks(): array
    {
        return [
            [$this->translator->trans('Texte (form)', [], 'admin'), 'form-text', 'form', 'fal text', false, Type\TextType::class],
            [$this->translator->trans('Zone de texte (form)', [], 'admin'), 'form-textarea', 'form', 'fal comment-alt', false, Type\TextareaType::class],
            [$this->translator->trans('Sélecteur (form)', [], 'admin'), 'form-choice-type', 'form', 'fal list-ul', false, Type\ChoiceType::class],
            [$this->translator->trans('Case à cocher (form)', [], 'admin'), 'form-checkbox', 'form', 'fal check-square', false, Type\CheckboxType::class],
            [$this->translator->trans('Email (form)', [], 'admin'), 'form-email', 'form', 'fal at', false, Type\EmailType::class],
            [$this->translator->trans('Téléphone (form)', [], 'admin'), 'form-phone', 'form', 'fal phone', false, Type\TelType::class],
            [$this->translator->trans('Code postal (form)', [], 'admin'), 'form-zip-code', 'form', 'fal mailbox', false, Type\TextType::class],
            [$this->translator->trans('Date (form)', [], 'admin'), 'form-date', 'form', 'fal calendar-alt', false, Type\DateType::class],
            [$this->translator->trans('Heure (form)', [], 'admin'), 'form-hour', 'form', 'fal clock', false, Type\TimeType::class],
            [$this->translator->trans('Date & heure (form)', [], 'admin'), 'form-datetime', 'form', 'fal calendar-star', false, Type\DateTimeType::class],
            [$this->translator->trans('Pièce jointe (form)', [], 'admin'), 'form-file', 'form', 'fal file', false, Type\FileType::class],
            [$this->translator->trans('Groupe de mails (form)', [], 'admin'), 'form-emails', 'form', 'fal users-class', false, Type\ChoiceType::class],
            [$this->translator->trans("Sélecteur d'entité (form)", [], 'admin'), 'form-choice-entity', 'form', 'fal cubes', false, EntityType::class],
            [$this->translator->trans('Nombre (form)', [], 'admin'), 'form-integer', 'form', 'fal sort-numeric-up-alt', false, Type\IntegerType::class],
            [$this->translator->trans('Pays (form)', [], 'admin'), 'form-country', 'form', 'fal map-marked', false, Type\CountryType::class],
            [$this->translator->trans('Langues (form)', [], 'admin'), 'form-language', 'form', 'fal flag', false, Type\LanguageType::class],
            [$this->translator->trans('URL (form)', [], 'admin'), 'form-url', 'form', 'fal link', false, Type\UrlType::class],
            [$this->translator->trans('RGPD (form)', [], 'admin'), 'form-gdpr', 'form', 'fal cookie', false, Type\CheckboxType::class],
            [$this->translator->trans('Caché (form)', [], 'admin'), 'form-hidden', 'form', 'fal mask', false, Type\HiddenType::class],
            [$this->translator->trans('Bouton de soumission (form)', [], 'admin'), 'form-submit', 'form', 'fal paper-plane', false, Type\SubmitType::class],
        ];
    }

    /**
     * Get BlockTypes config.
     */
    private function getLayoutBocks(): array
    {
        return [
            [$this->translator->trans('Entête (layout)', [], 'admin'), 'layout-title-header', 'layout', 'fal text-width'],
            [$this->translator->trans('Titre (layout)', [], 'admin'), 'layout-title', 'layout', 'fal text'],
            [$this->translator->trans('Texte (layout)', [], 'admin'), 'layout-body', 'layout', 'fal paragraph'],
            [$this->translator->trans('Introduction (layout)', [], 'admin'), 'layout-intro', 'layout', 'fal align-center'],
            [$this->translator->trans('Date de publication (layout)', [], 'admin'), 'layout-published-date', 'layout', 'fal calendar-alt'],
            [$this->translator->trans('Catégorie (layout)', [], 'admin'), 'layout-category', 'layout', 'fal bookmark'],
            [$this->translator->trans('Média (layout)', [], 'admin'), 'layout-image', 'layout', 'fal image'],
            [$this->translator->trans('Galerie (layout)', [], 'admin'), 'layout-gallery', 'layout', 'fal photo-video'],
            [$this->translator->trans('Carrousel (layout)', [], 'admin'), 'layout-slider', 'layout', 'fal images'],
            [$this->translator->trans('Vidéo (layout)', [], 'admin'), 'layout-video', 'layout', 'fal video'],
            [$this->translator->trans('Entités associées (layout)', [], 'admin'), 'layout-associated-entities', 'layout', 'fal list-ul'],
            [$this->translator->trans('Bouton de retour (layout)', [], 'admin'), 'layout-back-button', 'layout', 'fal reply'],
            [$this->translator->trans('Lien (layout)', [], 'admin'), 'layout-link', 'layout', 'fal link'],
            [$this->translator->trans('Boutons de partage (layout)', [], 'admin'), 'layout-share', 'layout', 'fal share-alt'],
            [$this->translator->trans('Informations de contact (layout)', [], 'admin'), 'layout-contact', 'layout', 'fal info'],
            [$this->translator->trans('Carte (layout custom)', [], 'admin'), 'layout-map', 'layout-map', 'fal map-marked'],
            [$this->translator->trans('Tableaux des lots (layout catalog)', [], 'admin'), 'layout-catalog-lots-table', 'layout-catalog', 'fal building'],
            [$this->translator->trans('Caractéristiques (layout catalog)', [], 'admin'), 'layout-catalog-features', 'layout-catalog', 'fal clipboard-list-check'],
            [$this->translator->trans('Produits associés (layout catalog)', [], 'admin'), 'layout-catalog-associated-products', 'layout-catalog', 'fal clipboard-list-check'],
        ];
    }

    /**
     * Get BlockTypes config.
     */
    private function getContentBlocks(): array
    {
        return [
            [$this->translator->trans('Entête', [], 'admin'), 'title-header', 'content', 'fal text-width'],
            [$this->translator->trans('Titre', [], 'admin'), 'title', 'global', 'fal text'],
            [$this->translator->trans('Texte', [], 'admin'), 'text', 'global', 'fal paragraph'],
            [$this->translator->trans('Média', [], 'admin'), 'media', 'global', 'fal image'],
            [$this->translator->trans('Lien', [], 'admin'), 'link', 'global', 'fal link'],
            [$this->translator->trans('Vidéo', [], 'admin'), 'video', 'content', 'fal video'],
            [$this->translator->trans('Mini fiche', [], 'admin'), 'card', 'content', 'fal bookmark', true],
            [$this->translator->trans('Citation', [], 'admin'), 'blockquote', 'content', 'fal quote-right', true],
            [$this->translator->trans('Collapse', [], 'admin'), 'collapse', 'content', 'fal line-height', true],
            [$this->translator->trans('Pop-up', [], 'admin'), 'modal', 'content', 'fal comment-alt', true],
            [$this->translator->trans('Alerte', [], 'admin'), 'alert', 'global', 'fal exclamation-triangle', true],
            [$this->translator->trans('Icône', [], 'admin'), 'icon', 'global', 'fab ravelry', true],
            [$this->translator->trans('Module', [], 'admin'), 'action', 'action', 'fal star', true],
            [$this->translator->trans('Séparateur', [], 'admin'), 'separator', 'global', 'fal grip-lines', true],
            [$this->translator->trans('Widget', [], 'admin'), 'widget', 'content', 'fal code', true],
//            [$this->translator->trans('Boutons de partages', [], 'admin'), 'share', 'global', 'fal share-alt', true, true],
            [$this->translator->trans('Compteur', [], 'admin'), 'counter', 'global', 'fal sort-numeric-up-alt', true],
            [$this->translator->trans('Boutons de partage', [], 'admin'), 'social-networks', 'global', 'fal share-alt', false, false],
            [$this->translator->trans('Navigation de zones', [], 'admin'), 'zones-navigation', 'global', 'fal bars', false, false],
            [$this->translator->trans('Action', [], 'admin'), 'core-action', 'core', 'fab superpowers'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
