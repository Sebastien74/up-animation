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
            [$this->translator->trans('Texte (form)', [], 'admin'), 'form-text', 'form', 'text', false, Type\TextType::class],
            [$this->translator->trans('Zone de texte (form)', [], 'admin'), 'form-textarea', 'form', 'comment-alt', false, Type\TextareaType::class],
            [$this->translator->trans('Sélecteur (form)', [], 'admin'), 'form-choice-type', 'form', 'list-ul', false, Type\ChoiceType::class],
            [$this->translator->trans('Case à cocher (form)', [], 'admin'), 'form-checkbox', 'form', 'check-square', false, Type\CheckboxType::class],
            [$this->translator->trans('Email (form)', [], 'admin'), 'form-email', 'form', 'at', false, Type\EmailType::class],
            [$this->translator->trans('Téléphone (form)', [], 'admin'), 'form-phone', 'form', 'phone', false, Type\TelType::class],
            [$this->translator->trans('Code postal (form)', [], 'admin'), 'form-zip-code', 'form', 'mailbox', false, Type\TextType::class],
            [$this->translator->trans('Date (form)', [], 'admin'), 'form-date', 'form', 'calendar-alt', false, Type\DateType::class],
            [$this->translator->trans('Heure (form)', [], 'admin'), 'form-hour', 'form', 'clock', false, Type\TimeType::class],
            [$this->translator->trans('Date & heure (form)', [], 'admin'), 'form-datetime', 'form', 'calendar-star', false, Type\DateTimeType::class],
            [$this->translator->trans('Pièce jointe (form)', [], 'admin'), 'form-file', 'form', 'file', false, Type\FileType::class],
            [$this->translator->trans('Groupe de mails (form)', [], 'admin'), 'form-emails', 'form', 'users-class', false, Type\ChoiceType::class],
            [$this->translator->trans("Sélecteur d'entité (form)", [], 'admin'), 'form-choice-entity', 'form', 'cubes', false, EntityType::class],
            [$this->translator->trans('Nombre (form)', [], 'admin'), 'form-integer', 'form', 'sort-numeric-up-alt', false, Type\IntegerType::class],
            [$this->translator->trans('Pays (form)', [], 'admin'), 'form-country', 'form', 'map-marked', false, Type\CountryType::class],
            [$this->translator->trans('Langues (form)', [], 'admin'), 'form-language', 'form', 'flag', false, Type\LanguageType::class],
            [$this->translator->trans('URL (form)', [], 'admin'), 'form-url', 'form', 'link', false, Type\UrlType::class],
            [$this->translator->trans('RGPD (form)', [], 'admin'), 'form-gdpr', 'form', 'cookie', false, Type\CheckboxType::class],
            [$this->translator->trans('Caché (form)', [], 'admin'), 'form-hidden', 'form', 'mask', false, Type\HiddenType::class],
            [$this->translator->trans('Bouton de soumission (form)', [], 'admin'), 'form-submit', 'form', 'paper-plane', false, Type\SubmitType::class],
        ];
    }

    /**
     * Get BlockTypes config.
     */
    private function getLayoutBocks(): array
    {
        return [
            [$this->translator->trans('Entête (layout)', [], 'admin'), 'layout-title-header', 'layout', 'text-width'],
            [$this->translator->trans('Titre (layout)', [], 'admin'), 'layout-title', 'layout', 'text'],
            [$this->translator->trans('Texte (layout)', [], 'admin'), 'layout-body', 'layout', 'paragraph'],
            [$this->translator->trans('Introduction (layout)', [], 'admin'), 'layout-intro', 'layout', 'align-center'],
            [$this->translator->trans('Date de publication (layout)', [], 'admin'), 'layout-published-date', 'layout', 'calendar-alt'],
            [$this->translator->trans('Catégorie (layout)', [], 'admin'), 'layout-category', 'layout', 'bookmark'],
            [$this->translator->trans('Média (layout)', [], 'admin'), 'layout-image', 'layout', 'image'],
            [$this->translator->trans('Galerie (layout)', [], 'admin'), 'layout-gallery', 'layout', 'photo-video'],
            [$this->translator->trans('Carrousel (layout)', [], 'admin'), 'layout-slider', 'layout', 'images'],
            [$this->translator->trans('Vidéo (layout)', [], 'admin'), 'layout-video', 'layout', 'video'],
            [$this->translator->trans('Entités associées (layout)', [], 'admin'), 'layout-associated-entities', 'layout', 'list-ul'],
            [$this->translator->trans('Bouton de retour (layout)', [], 'admin'), 'layout-back-button', 'layout', 'reply'],
            [$this->translator->trans('Lien (layout)', [], 'admin'), 'layout-link', 'layout', 'link'],
            [$this->translator->trans('Boutons de partage (layout)', [], 'admin'), 'layout-share', 'layout', 'share-alt'],
            [$this->translator->trans('Informations de contact (layout)', [], 'admin'), 'layout-contact', 'layout', 'info'],
            [$this->translator->trans('Carte (layout custom)', [], 'admin'), 'layout-map', 'layout-map', 'map-marked'],
            [$this->translator->trans('Tableaux des lots (layout catalog)', [], 'admin'), 'layout-catalog-lots-table', 'layout-catalog', 'building'],
            [$this->translator->trans('Caractéristiques (layout catalog)', [], 'admin'), 'layout-catalog-features', 'layout-catalog', 'clipboard-list-check'],
            [$this->translator->trans('Produits associés (layout catalog)', [], 'admin'), 'layout-catalog-associated-products', 'layout-catalog', 'clipboard-list-check'],
        ];
    }

    /**
     * Get BlockTypes config.
     */
    private function getContentBlocks(): array
    {
        return [
            [$this->translator->trans('Entête', [], 'admin'), 'title-header', 'content', 'text-width'],
            [$this->translator->trans('Titre', [], 'admin'), 'title', 'global', 'text'],
            [$this->translator->trans('Texte', [], 'admin'), 'text', 'global', 'paragraph'],
            [$this->translator->trans('Média', [], 'admin'), 'media', 'global', 'image'],
            [$this->translator->trans('Lien', [], 'admin'), 'link', 'global', 'link'],
            [$this->translator->trans('Vidéo', [], 'admin'), 'video', 'content', 'video'],
            [$this->translator->trans('Mini fiche', [], 'admin'), 'card', 'content', 'bookmark', true],
            [$this->translator->trans('Citation', [], 'admin'), 'blockquote', 'content', 'quote-right', true],
            [$this->translator->trans('Collapse', [], 'admin'), 'collapse', 'content', 'line-height', true],
            [$this->translator->trans('Pop-up', [], 'admin'), 'modal', 'content', 'comment-alt', true],
            [$this->translator->trans('Alerte', [], 'admin'), 'alert', 'global', 'exclamation-triangle', true],
            [$this->translator->trans('Icône', [], 'admin'), 'icon', 'global', 'ravelry', true],
            [$this->translator->trans('Module', [], 'admin'), 'action', 'action', 'star', true],
            [$this->translator->trans('Séparateur', [], 'admin'), 'separator', 'global', 'grip-lines', true],
            [$this->translator->trans('Widget', [], 'admin'), 'widget', 'content', 'code', true],
//            [$this->translator->trans('Boutons de partages', [], 'admin'), 'share', 'global', 'share-alt', true, true],
            [$this->translator->trans('Compteur', [], 'admin'), 'counter', 'global', 'sort-numeric-up-alt', true],
            [$this->translator->trans('Boutons de partage', [], 'admin'), 'social-networks', 'global', 'share-alt', false, false],
            [$this->translator->trans('Navigation de zones', [], 'admin'), 'zones-navigation', 'global', 'bars', false, false],
            [$this->translator->trans('Action', [], 'admin'), 'core-action', 'core', 'superpowers'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
