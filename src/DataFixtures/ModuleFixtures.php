<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Core\Module;
use App\Entity\Security\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * ModuleFixtures.
 *
 * Module Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModuleFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private int $position = 1;

    protected function loadData(ObjectManager $manager): void
    {
        foreach ($this->getModules() as $config) {
            $module = $this->generateModule($config);
            $this->addReference($module->getSlug(), $module);
            $this->manager->persist($module);
        }
        $this->manager->flush();
    }

    /**
     * Generate BlockType.
     */
    private function generateModule(array $config): Module
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        $module = new Module();
        $module->setAdminName($config[0]);
        $module->setSlug($config[1]);
        $module->setRole($config[2]);
        $module->setIconClass($config[3]);
        $module->setPosition($this->position);
        $module->setCreatedBy($user);

        ++$this->position;

        $this->manager->persist($module);

        return $module;
    }

    /**
     * Get Modules config.
     */
    private function getModules(): array
    {
        return [
            [$this->translator->trans('Pages', [], 'admin'), 'pages', 'ROLE_PAGE', 'network-wired'],
            [$this->translator->trans('Google analytics', [], 'admin'), 'google-analytics', 'ROLE_GOOGLE_ANALYTICS', 'chart-line'],
            [$this->translator->trans('Informations', [], 'admin'), 'information', 'ROLE_INFORMATION', 'info'],
            [$this->translator->trans('Formulaires', [], 'admin'), 'form', 'ROLE_FORM', 'wpforms'],
            [$this->translator->trans('Calendriers de formulaire', [], 'admin'), 'form-calendar', 'ROLE_FORM_CALENDAR', 'calendar-plus'],
            [$this->translator->trans('Formulaires à étapes', [], 'admin'), 'steps-form', 'ROLE_STEP_FORM', 'wpforms'],
            [$this->translator->trans('Galeries', [], 'admin'), 'gallery', 'ROLE_GALLERY', 'photo-video'],
            [$this->translator->trans('Médias', [], 'admin'), 'medias', 'ROLE_MEDIA', 'photo-video'],
            [$this->translator->trans('Actualités', [], 'admin'), 'newscast', 'ROLE_NEWSCAST', 'newspaper'],
            [$this->translator->trans('Navigations', [], 'admin'), 'navigation', 'ROLE_NAVIGATION', 'bars'],
            [$this->translator->trans('Newsletters', [], 'admin'), 'newsletter', 'ROLE_NEWSLETTER', 'typewriter'],
            [$this->translator->trans('Tableaux', [], 'admin'), 'table', 'ROLE_TABLE', 'table'],
            [$this->translator->trans('FAQ', [], 'admin'), 'faq', 'ROLE_FAQ', 'question'],
            [$this->translator->trans('Plan de site', [], 'admin'), 'sitemap', 'ROLE_SITE_MAP', 'sitemap'],
            [$this->translator->trans('Cartes', [], 'admin'), 'map', 'ROLE_MAP', 'map-marked'],
            [$this->translator->trans("Groupes d'onglets", [], 'admin'), 'tab', 'ROLE_TAB', 'layer-group'],
            [$this->translator->trans('Moteurs de recherche', [], 'admin'), 'search', 'ROLE_SEARCH_ENGINE', 'search'],
            [$this->translator->trans('RGPD', [], 'admin'), 'gdpr', 'ROLE_INTERNAL', 'cookie'],
            [$this->translator->trans('Référencement', [], 'admin'), 'seo', 'ROLE_SEO', 'chart-line'],
            [$this->translator->trans('Carrousels', [], 'admin'), 'slider', 'ROLE_SLIDER', 'images'],
            [$this->translator->trans('Navigation de pages associées', [], 'admin'), 'pages-navigation', 'ROLE_PAGE', 'list'],
            [$this->translator->trans('Portfolios', [], 'admin'), 'portfolio', 'ROLE_PORTFOLIO', 'photo-video'],
            [$this->translator->trans('Agendas', [], 'admin'), 'agenda', 'ROLE_AGENDA', 'calendar-alt'],
            [$this->translator->trans('Catalogues', [], 'admin'), 'catalog', 'ROLE_CATALOG', 'book-open'],
            [$this->translator->trans('Chronologies', [], 'admin'), 'timeline', 'ROLE_TIMELINE', 'clock'],
            [$this->translator->trans('Recrutements', [], 'admin'), 'recruitment', 'ROLE_RECRUITMENT', 'file-certificate'],
            [$this->translator->trans('Informations de contact', [], 'admin'), 'contact-information', 'ROLE_CONTACT', 'info'],
            [$this->translator->trans('Traductions', [], 'admin'), 'translation', 'ROLE_TRANSLATION', 'globe-stand'],
            [$this->translator->trans('Utilisateurs', [], 'admin'), 'user', 'ROLE_USERS', 'users'],
            [$this->translator->trans('Actions personnalisées', [], 'admin'), 'customs-actions', 'ROLE_CUSTOMS_ACTIONS', 'flame'],
            [$this->translator->trans('Pages sécurisées (Users front)', [], 'admin'), 'secure-page', 'ROLE_SECURE_PAGE', 'shield'],
            [$this->translator->trans('Modules sécurisés', [], 'admin'), 'secure-module', 'ROLE_SECURE_MODULE', 'shield'],
            [$this->translator->trans('Classes personnalisées', [], 'admin'), 'css', 'ROLE_INTERNAL', 'paint-brush'],
            [$this->translator->trans('Édition générale', [], 'admin'), 'edit', 'ROLE_EDIT', 'pen-nib'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
