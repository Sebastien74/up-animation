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
            [$this->translator->trans('Pages', [], 'admin'), 'pages', 'ROLE_PAGE', 'fal network-wired'],
            [$this->translator->trans('Google analytics', [], 'admin'), 'google-analytics', 'ROLE_GOOGLE_ANALYTICS', 'fal chart-line'],
            [$this->translator->trans('Informations', [], 'admin'), 'information', 'ROLE_INFORMATION', 'fal info'],
            [$this->translator->trans('Formulaires', [], 'admin'), 'form', 'ROLE_FORM', 'fab wpforms'],
            [$this->translator->trans('Calendriers de formulaire', [], 'admin'), 'form-calendar', 'ROLE_FORM_CALENDAR', 'fal calendar-plus'],
            [$this->translator->trans('Formulaires à étapes', [], 'admin'), 'steps-form', 'ROLE_STEP_FORM', 'fab wpforms'],
            [$this->translator->trans('Galeries', [], 'admin'), 'gallery', 'ROLE_GALLERY', 'fal photo-video'],
            [$this->translator->trans('Médias', [], 'admin'), 'medias', 'ROLE_MEDIA', 'fal photo-video'],
            [$this->translator->trans('Actualités', [], 'admin'), 'newscast', 'ROLE_NEWSCAST', 'fal newspaper'],
            [$this->translator->trans('Navigations', [], 'admin'), 'navigation', 'ROLE_NAVIGATION', 'fal bars'],
            [$this->translator->trans('Newsletters', [], 'admin'), 'newsletter', 'ROLE_NEWSLETTER', 'fal typewriter'],
            [$this->translator->trans('Tableaux', [], 'admin'), 'table', 'ROLE_TABLE', 'fal table'],
            [$this->translator->trans('FAQ', [], 'admin'), 'faq', 'ROLE_FAQ', 'fal question'],
            [$this->translator->trans('Plan de site', [], 'admin'), 'sitemap', 'ROLE_SITE_MAP', 'fal sitemap'],
            [$this->translator->trans('Cartes', [], 'admin'), 'map', 'ROLE_MAP', 'fal map-marked'],
            [$this->translator->trans("Groupes d'onglets", [], 'admin'), 'tab', 'ROLE_TAB', 'fal layer-group'],
            [$this->translator->trans('Moteurs de recherche', [], 'admin'), 'search', 'ROLE_SEARCH_ENGINE', 'fal search'],
            [$this->translator->trans('RGPD', [], 'admin'), 'gdpr', 'ROLE_INTERNAL', 'fal cookie'],
            [$this->translator->trans('Référencement', [], 'admin'), 'seo', 'ROLE_SEO', 'fal chart-line'],
            [$this->translator->trans('Carrousels', [], 'admin'), 'slider', 'ROLE_SLIDER', 'fal images'],
            [$this->translator->trans('Navigation de pages associées', [], 'admin'), 'pages-navigation', 'ROLE_PAGE', 'fal list'],
            [$this->translator->trans('Portfolios', [], 'admin'), 'portfolio', 'ROLE_PORTFOLIO', 'fal photo-video'],
            [$this->translator->trans('Agendas', [], 'admin'), 'agenda', 'ROLE_AGENDA', 'fal calendar-alt'],
            [$this->translator->trans('Catalogues', [], 'admin'), 'catalog', 'ROLE_CATALOG', 'fal book-open'],
            [$this->translator->trans('Chronologies', [], 'admin'), 'timeline', 'ROLE_TIMELINE', 'fal clock'],
            [$this->translator->trans('Recrutements', [], 'admin'), 'recruitment', 'ROLE_RECRUITMENT', 'fal file-certificate'],
            [$this->translator->trans('Informations de contact', [], 'admin'), 'contact-information', 'ROLE_CONTACT', 'fal info'],
            [$this->translator->trans('Traductions', [], 'admin'), 'translation', 'ROLE_TRANSLATION', 'fal globe-stand'],
            [$this->translator->trans('Utilisateurs', [], 'admin'), 'user', 'ROLE_USERS', 'fal users'],
            [$this->translator->trans('Actions personnalisées', [], 'admin'), 'customs-actions', 'ROLE_CUSTOMS_ACTIONS', 'fal flame'],
            [$this->translator->trans('Pages sécurisées (Users front)', [], 'admin'), 'secure-page', 'ROLE_SECURE_PAGE', 'fal shield'],
            [$this->translator->trans('Modules sécurisés', [], 'admin'), 'secure-module', 'ROLE_SECURE_MODULE', 'fal shield'],
            [$this->translator->trans('Classes personnalisées', [], 'admin'), 'css', 'ROLE_INTERNAL', 'fal paint-brush'],
            [$this->translator->trans('Édition générale', [], 'admin'), 'edit', 'ROLE_EDIT', 'fal pen-nib'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
