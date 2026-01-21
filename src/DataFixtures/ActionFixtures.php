<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Controller\Front\Action as Controller;
use App\Controller\Security\Front as SecurityController;
use App\Entity\Core\Module;
use App\Entity\Layout\Action;
use App\Entity\Layout\Page;
use App\Entity\Module\Agenda\Agenda;
use App\Entity\Module\Catalog as CatalogEntities;
use App\Entity\Module\Contact\Contact;
use App\Entity\Module\Faq\Faq;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Form\StepForm;
use App\Entity\Module\Gallery as GalleryEntities;
use App\Entity\Module\Map\Map;
use App\Entity\Module\Menu\Menu;
use App\Entity\Module\Newscast as NewscastEntities;
use App\Entity\Module\Newsletter\Campaign;
use App\Entity\Module\Portfolio as PortfolioEntities;
use App\Entity\Module\Recruitment as RecruitmentEntities;
use App\Entity\Module\Search\Search;
use App\Entity\Module\Slider\Slider;
use App\Entity\Module\Tab\Tab;
use App\Entity\Module\Table\Table;
use App\Entity\Module\Timeline\Timeline;
use App\Entity\Security\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * ActionFixtures.
 *
 * Action Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private int $position = 1;

    protected function loadData(ObjectManager $manager): void
    {
        foreach ($this->getActions() as $config) {
            $action = $this->addAction($config);
            $this->addReference($action->getSlug(), $action);
        }
    }

    /**
     * Generate Action.
     */
    private function addAction(array $config): Action
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        /** @var Module $module */
        $module = $this->getReference($config[6], Module::class);

        $action = new Action();
        $action->setAdminName($config[0]);
        $action->setController($config[1]);
        $action->setAction($config[2]);
        $action->setEntity($config[3]);
        $action->setSlug($config[4]);
        $action->setIconClass($config[5]);
        $action->setModule($module);
        $action->setDropdown(!empty($config[7]));
        $action->setPosition($this->position);
        $action->setCreatedBy($user);

        ++$this->position;
        $this->manager->persist($action);
        $this->manager->flush();

        return $action;
    }

    /**
     * Get Actions config.
     */
    private function getActions(): array
    {
        return [
            [$this->translator->trans('Carrousel', [], 'admin'), Controller\SliderController::class, 'view', Slider::class, 'slider-view', 'fal images', 'slider'],
            [$this->translator->trans('Galerie', [], 'admin'), Controller\GalleryController::class, 'view', GalleryEntities\Gallery::class, 'gallery-view', 'fal photo-video', 'gallery', true],
            [$this->translator->trans('Teaser de galeries', [], 'admin'), Controller\GalleryController::class, 'teaser', GalleryEntities\Teaser::class, 'gallery-teaser', 'fal photo-video', 'gallery', true],
            [$this->translator->trans('Liste de galeries', [], 'admin'), Controller\GalleryController::class, 'index', GalleryEntities\Listing::class, 'gallery-index', 'fal photo-video', 'gallery', true],
            [$this->translator->trans('Formulaire', [], 'admin'), Controller\FormController::class, 'view', Form::class, 'form-view', 'fab wpforms', 'form'],
            [$this->translator->trans('Formulaire page de succès', [], 'admin'), Controller\FormController::class, 'success', Form::class, 'form-success', 'fal ballot-check', 'form', true],
            [$this->translator->trans('Formulaire newsletter', [], 'admin'), Controller\NewsletterController::class, 'view', Campaign::class, 'newsletter-form', 'fab wpforms', 'form'],
            [$this->translator->trans('Calendrier de formulaire', [], 'admin'), Controller\FormController::class, 'calendar', null, 'form-calendar-view', 'fal calendar-plus', 'form-calendar', true],
            [$this->translator->trans('Formulaire à étapes', [], 'admin'), Controller\FormController::class, 'step', StepForm::class, 'form-step', 'fab wpforms', 'steps-form', true],
            [$this->translator->trans("Teaser d'actualités", [], 'admin'), Controller\NewscastController::class, 'teaser', NewscastEntities\Teaser::class, 'newscast-teaser', 'fal newspaper', 'newscast'],
            [$this->translator->trans("Liste d'actualités", [], 'admin'), Controller\NewscastController::class, 'index', NewscastEntities\Listing::class, 'newscast-index', 'fal newspaper', 'newscast'],
            [$this->translator->trans('Teaser portfolio', [], 'admin'), Controller\PortfolioController::class, 'teaser', PortfolioEntities\Teaser::class, 'portfolio-teaser', 'fal photo-video', 'portfolio', true],
            [$this->translator->trans('Portfolio', [], 'admin'), Controller\PortfolioController::class, 'index', PortfolioEntities\Listing::class, 'portfolio-index', 'fal photo-video', 'portfolio', true],
            [$this->translator->trans('Menu', [], 'admin'), null, 'view', Menu::class, 'menu-view', 'fal bars', 'navigation', true],
            [$this->translator->trans('Plan de site', [], 'admin'), Controller\SitemapController::class, 'view', null, 'sitemap-view', 'fal sitemap', 'sitemap', true],
            [$this->translator->trans('Tableau', [], 'admin'), Controller\TableController::class, 'view', Table::class, 'table-view', 'fal table', 'table'],
            [$this->translator->trans('FAQ', [], 'admin'), Controller\FaqController::class, 'view', Faq::class, 'faq-view', 'fal question', 'faq'],
            [$this->translator->trans('FAQ Teaser', [], 'admin'), Controller\FaqController::class, 'teaser', Faq::class, 'faq-teaser', 'fal question', 'faq'],
            [$this->translator->trans('Carte', [], 'admin'), Controller\MapController::class, 'view', Map::class, 'map-view', 'fal map-marked', 'map'],
            [$this->translator->trans('Informations de contact', [], 'admin'), Controller\InformationController::class, 'view', null, 'information-view', 'fal info', 'information', true],
            [$this->translator->trans("Groupe d'onglets", [], 'admin'), Controller\TabController::class, 'view', Tab::class, 'tab-view', 'fal layer-group', 'tab', true],
            [$this->translator->trans('Moteur de recherche', [], 'admin'), Controller\SearchController::class, 'view', Search::class, 'search-view', 'fal search', 'search', true],
            [$this->translator->trans('Moteur de recherche & résultats', [], 'admin'), Controller\SearchController::class, 'results', Search::class, 'search-result-view', 'fal search', 'search', true],
            [$this->translator->trans('Informations de contact', [], 'admin'), Controller\ContactController::class, 'contact', Contact::class, 'contact-information-view', 'fal file-signature', 'contact-information', true],
            [$this->translator->trans('Agenda', [], 'admin'), Controller\AgendaController::class, 'view', Agenda::class, 'agenda-view', 'fal calendar-alt', 'agenda', true],
            [$this->translator->trans('Timeline', [], 'admin'), Controller\TimelineController::class, 'view', Timeline::class, 'timeline-view', 'fal clock', 'timeline', true],
            [$this->translator->trans("Liste des offres d'emplois", [], 'admin'), Controller\RecruitmentController::class, 'index', RecruitmentEntities\Listing::class, 'recruitment-index', 'fal file-certificate', 'recruitment', true],
            [$this->translator->trans('Liste des produits', [], 'admin'), Controller\CatalogController::class, 'index', CatalogEntities\Listing::class, 'catalog-index', 'fal book-open', 'catalog', true],
            [$this->translator->trans('Teaser de produits', [], 'admin'), Controller\CatalogController::class, 'teaser', CatalogEntities\Teaser::class, 'catalog-teaser', 'fal book-open', 'catalog', true],
            [$this->translator->trans('Teaser de catégories de produits', [], 'admin'), Controller\CatalogController::class, 'teaserCategories', null, 'catalog-teaser-categories', 'fal book-open', 'catalog', true],
            [$this->translator->trans('Panier de produits', [], 'admin'), Controller\CatalogController::class, 'cart', null, 'catalog-cart', 'fal cart-arrow-down', 'catalog', true],
            [$this->translator->trans('Tableau de bord page sécurisée', [], 'admin'), SecurityController\FrontController::class, 'dashboard', null, 'secure-page-dashboard', 'fal tachometer', 'secure-page', true],
            [$this->translator->trans('Navigation de pages associées', [], 'admin'), SecurityController\FrontController::class, 'view', Page::class, 'pages-navigation-view', 'fal list', 'pages-navigation', true],
        ];
    }

    public function getDependencies(): array
    {
        return [
            ModuleFixtures::class,
            SecurityFixtures::class,
        ];
    }
}
