<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Media\Media;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Module\Form as FormEntity;
use App\Entity\Module\Map as MapEntity;
use App\Entity\Module\Newscast as NewsEntity;
use App\Entity\Module\Catalog as CatalogEntity;
use App\Entity\Module\Search\Search;
use App\Entity\Module\Slider\Slider;
use App\Entity\Module\Slider\SliderMediaRelation;
use App\Entity\Security\User;
use App\Entity\Seo\Url;
use App\Form\Manager\Layout\LayoutManager;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * PageFixtures.
 *
 * Page Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => PageFixtures::class, 'key' => 'page_fixtures'],
])]
class PageFixtures
{
    private const bool CONTACT_LAYOUT = true;
    private const bool CONTACT_MAP = true;
    private array $yamlConfiguration = [];
    private Generator $faker;
    private Website $website;
    private ?User $user;
    private bool $flush;
    private string $locale = '';
    private array $pages = [];
    private int $layoutPosition = 1;

    /**
     * PageFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LayoutManager $layoutManager,
    ) {
    }

    /**
     * Add Pages.
     *
     * @throws Exception
     */
    public function add(Website $website, array $yamlConfiguration, array $pagesParams, ?User $user = null, bool $flush = true, array $mainPages = []): array
    {
        $this->yamlConfiguration = $yamlConfiguration;
        $this->faker = Factory::create();
        $this->website = $website;
        $this->user = $user;
        $this->flush = $flush;
        $this->locale = $website->getConfiguration()->getLocale();
        $this->layoutPosition = count($this->coreLocator->em()->getRepository(Layout\Layout::class)->findBy(['website' => $this->website])) + 1;

        foreach ($pagesParams as $key => $pageParams) {
            $params = (object) $pageParams;
            $enable = !property_exists($params, 'disable') || false === $params->disable;
            if ($enable) {
                $existingPage = $this->coreLocator->em()->getRepository(Layout\Page::class)->findOneBy([
                    'website' => $website,
                    'slug' => $params->reference,
                ]);
                if (!$existingPage) {
                    $layout = $this->addLayoutPage($params);
                    $position = $website->getId() > 0 ? count($this->coreLocator->em()->getRepository(Layout\Page::class)->findByWebsiteNotArchived($website)) + 1 : $key + 1;
                    $this->generatePage($layout, $params, $position, $mainPages);
                    $this->coreLocator->em()->persist($layout);
                }
            }
        }

        return $this->pages;
    }

    /**
     * Generate Page.
     */
    private function generatePage(Layout\Layout $layout, mixed $params, int $position, array $mainPages = []): void
    {
        $page = new Layout\Page();
        $page->setAdminName($params->name);
        $page->setWebsite($this->website);
        $page->setAsIndex($params->asIndex);
        $page->setTemplate($params->template.'.html.twig');
        $page->setPosition($position);
        $page->setDeletable($params->deletable);
        $page->setSlug($params->reference);
        $page->setLayout($layout);
        $page->setCreatedBy($this->user);

        if (!$params->deletable) {
            $page->setInfill(true);
        }

        if (property_exists($params, 'secure')) {
            $page->setSecure($params->secure);
        }

        $this->coreLocator->em()->persist($page);
        $this->pages[$params->reference] = $page;

        $this->generateUrl($page, $params->urlAsIndex);

        if (in_array($params->reference, $mainPages)) {
            $configuration = $this->website->getConfiguration();
            $configuration->addPage($page);
            $this->coreLocator->em()->persist($configuration);
        }
    }

    /**
     * Generate Url.
     */
    private function generateUrl(Layout\Page $page, bool $asIndex): void
    {
        $url = new Url();
        $url->setCode(Urlizer::urlize($page->getAdminName()));
        $url->setLocale($this->locale);
        $url->setWebsite($this->website);
        $url->setAsIndex($asIndex);
        $url->setHideInSitemap(!$asIndex);
        $url->setOnline(true);

        if (!empty($this->user)) {
            $url->setCreatedBy($this->user);
        }

        $page->addUrl($url);

        $this->coreLocator->em()->persist($page);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }
    }

    /**
     * Generate Layout Page.
     *
     * @throws Exception
     */
    private function addLayoutPage(mixed $params): Layout\Layout
    {
        $layout = $this->addLayout($params->name);

        /* Header */
        if ('home' != $params->reference) {
            $zone = $this->addZone($layout, 1, true);
            $col = $this->addCol($zone);
            $this->addHeader($col, $params->name);
        }

        if ('home' == $params->reference) {
            $this->addHomeLayout($layout);
        } elseif ('news' == $params->reference) {
            $this->addNewscastLayout($layout);
        } elseif ('products' == $params->reference) {
            $this->addProductLayout($layout);
        } elseif ('sitemap' == $params->reference) {
            $this->addSitemapLayout($layout);
        } elseif ('contact' == $params->reference) {
            $this->addContactLayout($layout);
        } elseif ('user-dashboard' == $params->reference) {
            $this->addUserDashboardLayout($layout);
        } elseif ('search-results' == $params->reference) {
            $this->addSearchResultsLayout($layout);
        }

        $this->coreLocator->em()->persist($layout);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }

        $this->layoutManager->setGridZone($layout);

        return $layout;
    }

    /**
     * Generate Layout.
     */
    public function addLayout(string $adminName, bool $dbPosition = false): Layout\Layout
    {
        $position = $dbPosition ? count($this->coreLocator->em()->getRepository(Layout\Layout::class)->findBy(['website' => $this->website])) + 1 : $this->layoutPosition;

        $layout = new Layout\Layout();
        $layout->setWebsite($this->website);
        $layout->setAdminName($adminName);
        $layout->setPosition($position);

        if (!empty($this->user)) {
            $layout->setCreatedBy($this->user);
        }

        ++$this->layoutPosition;

        return $layout;
    }

    /**
     * Add Zone.
     */
    public function addZone(Layout\Layout $layout, int $position, bool $fullSize = false, bool $noPadding = false): Layout\Zone
    {
        $zone = new Layout\Zone();
        $zone->setFullSize($fullSize);
        $zone->setPosition($position);

        if ($noPadding) {
            $zone->setPaddingTop('pt-0');
            $zone->setPaddingBottom('pb-0');
        }

        if (!empty($this->user)) {
            $zone->setCreatedBy($this->user);
        }

        $layout->addZone($zone);

        return $zone;
    }

    /**
     * Add Col.
     */
    public function addCol(Layout\Zone $zone, int $position = 1, int $size = 12): Layout\Col
    {
        $col = new Layout\Col();
        $col->setPosition($position);
        $col->setSize($size);
        $zone->addCol($col);

        if (!empty($this->user)) {
            $col->setCreatedBy($this->user);
        }

        return $col;
    }

    /**
     * Add header.
     */
    private function addHeader(Layout\Col $col, string $adminName): void
    {
        $col->setPaddingLeft('ps-0');
        $col->setPaddingRight('pe-0');

        $intl = new Layout\BlockIntl();
        $intl->setTitle($adminName);
        $intl->setLocale($this->locale);
        $intl->setTitleForce(1);
        $intl->setWebsite($this->website);

        $zone = $col->getZone();
        $zone->setPaddingTop('pt-0');
        $zone->setPaddingBottom('pb-0');

        if (!empty($this->user)) {
            $intl->setCreatedBy($this->user);
        }

        $block = $this->addBlock($col, 'title-header');
        $block->addIntl($intl);
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');
    }

    /**
     * Add Block.
     */
    public function addBlock(
        Layout\Col $col,
        ?string $blockTypeSlug = null,
        ?string $actionSlug = null,
        ?int $actionFilter = null,
        int $position = 1,
        int $size = 12,
        bool $maxTablet = false,
    ): Layout\Block {

        $block = new Layout\Block();
        $block->setPosition($position);
        $block->setSize($size);

        if (!empty($this->user)) {
            $block->setCreatedBy($this->user);
        }

        if ('form-submit' === $blockTypeSlug) {
            $block->setColor('btn-primary');
        }

        if ($maxTablet) {
            $block->setTabletSize($size);
            $block->setMiniPcSize($size);
        }

        $col->addBlock($block);

        $this->addAction($block, $blockTypeSlug, $actionSlug, $actionFilter);

        return $block;
    }

    /**
     * Add Home Layout.
     */
    private function addHomeLayout(Layout\Layout $layout): void
    {
        $slider = new Slider();
        $slider->setAdminName("Carrousel page d'accueil");
        $slider->setWebsite($this->website);
        $slider->setSlug('home-slider');
        $slider->setTemplate('two-columns');
        $slider->setArrowColor('btn-white');

        $media = $this->coreLocator->em()->getRepository(Media::class)->findOneBy([
            'website' => $this->website,
            'category' => 'title-header',
        ]);

        for ($i = 1; $i <= 2; ++$i) {
            $intl = new MediaRelationIntl();
            $intl->setLocale($this->locale);
            $intl->setTitle($this->faker->text(30));
            $intl->setBody('<p>'.$this->faker->text(100).'</p>');
            $intl->setIntroduction($this->faker->text(50));
            $intl->setTargetLink('https://www.felix-creation.fr/');
            $intl->setNewTab(true);
            $intl->setWebsite($this->website);
            $mediaRelation = new SliderMediaRelation();
            $mediaRelation->setPosition($i);
            $mediaRelation->setLocale($this->locale);
            $mediaRelation->setMedia($media);
            $mediaRelation->setIntl($intl);
            $slider->addMediaRelation($mediaRelation);
        }

        $this->coreLocator->em()->persist($slider);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }

        $carouselZone = $this->addZone($layout, 1, true, true);
        $carouselCol = $this->addCol($carouselZone);
        $carouselCol->setPaddingRight('pe-0');
        $carouselCol->setPaddingLeft('ps-0');
        $block = $this->addBlock($carouselCol, 'core-action', 'slider-view', $slider->getId());
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');

        $position = 2;

        for ($i = 1; $i <= 2; ++$i) {

            $contentZone = $this->addZone($layout, $position);
            $contentZone->setBackgroundColor('bg-primary');
            $textCol = $this->addCol($contentZone, 1, 6);
            $this->addBlock($textCol, 'title');
            $block = $this->addBlock($textCol, 'text', null, null, 2);
            $block->setMarginBottom('mb-md');
            $this->addBlock($textCol, 'link', null, null, 3);
            $imageCol = $this->addCol($contentZone, 2, 6);
            $this->addBlock($imageCol, 'media');
            ++$position;

            $contentZone = $this->addZone($layout, $position);
            $imageCol = $this->addCol($contentZone, 1, 6);
            $this->addBlock($imageCol, 'media');
            $textCol = $this->addCol($contentZone, 2, 6);
            $this->addBlock($textCol, 'title');
            $block = $this->addBlock($textCol, 'text', null, null, 2);
            $block->setMarginBottom('mb-md');
            $this->addBlock($textCol, 'link', null, null, 3);
            ++$position;
        }

        $teaser = $this->coreLocator->em()->getRepository(NewsEntity\Teaser::class)->findOneBy(['website' => $this->website]);
        if ($teaser instanceof NewsEntity\Teaser) {
            $teaser->setItemsPerSlide(4);
            $teaser->setNbrItems(15);
            $teaser->setTemplate('slider');
            $teaserZone = $this->addZone($layout, $position);
            $teaserZone->setBackgroundColor('bg-light');
            $teaserZone->setColToRight(true);
            $teaserCol = $this->addCol($teaserZone);
            $block = $this->addBlock($teaserCol, 'core-action', 'newscast-teaser', $teaser->getId());
            $block->setPaddingRight('pe-0');
        }
    }

    /**
     * Add Newscast Layout.
     */
    private function addNewscastLayout(Layout\Layout $layout): void
    {
        $listing = $this->coreLocator->em()->getRepository(NewsEntity\Listing::class)->findOneBy(['website' => $this->website]);
        $zone = $this->addZone($layout, 2, true, true);
        $col = $this->addCol($zone);
        $col->setPaddingRight('pe-0');
        $col->setPaddingLeft('ps-0');
        $block = $this->addBlock($col, 'core-action', 'newscast-index', $listing->getId());
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');
    }

    /**
     * Add Products Layout.
     */
    private function addProductLayout(Layout\Layout $layout): void
    {
        $listing = $this->coreLocator->em()->getRepository(CatalogEntity\Listing::class)->findOneBy(['website' => $this->website]);
        $zone = $this->addZone($layout, 2);
        $col = $this->addCol($zone);
        $this->addBlock($col, 'core-action', 'catalog-index', $listing->getId());
    }

    /**
     * Add Sitemap Layout.
     */
    private function addSitemapLayout(Layout\Layout $layout): void
    {
        $zone = $this->addZone($layout, 2);
        $col = $this->addCol($zone);
        $this->addBlock($col, 'core-action', 'sitemap-view');
    }

    /**
     * Add Contact Layout.
     *
     * @throws Exception
     */
    private function addContactLayout(Layout\Layout $layout): void
    {
        $zone = $this->addZone($layout, 2);
        if (self::CONTACT_LAYOUT) {
            $this->addForm($zone);
            $this->addInformation($zone);
            if (self::CONTACT_MAP) {
                $this->addMap($layout);
            }
        }
    }

    /**
     * Add User Dashboard Layout.
     *
     * @throws Exception
     */
    private function addUserDashboardLayout(Layout\Layout $layout): void
    {
        $zone = $this->addZone($layout, 2);
        $col = $this->addCol($zone, 2);
        $this->addBlock($col, 'core-action', 'secure-page-dashboard');
    }

    /**
     * Add Search page Layout.
     *
     * @throws Exception
     */
    private function addSearchResultsLayout(Layout\Layout $layout): void
    {
        $search = $this->coreLocator->em()->getRepository(Search::class)->findOneBy(['slug' => 'main', 'website' => $this->website]);
        if (!$search) {
            $search = new Search();
            $search->setWebsite($this->website);
            $search->setAdminName('Principal');
            $search->setSlug('main');
            $search->setCreatedBy($this->user);
            $search->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        }

        $this->coreLocator->em()->persist($search);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }

        $zone = $this->addZone($layout, 2);
        $col = $this->addCol($zone);
        $this->addBlock($col, 'core-action', 'search-result-view', $search->getId());
    }

    /**
     * Add Form to contact Page.
     *
     * @throws Exception
     */
    private function addForm(Layout\Zone $zone): void
    {
        $adminName = 'Formulaire de contact';
        $form = $this->setForm($adminName, 'contact');
        if (!empty($this->user)) {
            $form->setCreatedBy($this->user);
        }

        $formLayout = $this->addLayout($adminName);
        $zoneLayout = $this->addZone($formLayout, 1, false, true);
        $zoneLayout->setAsSection(false);

        $col = $this->addCol($zoneLayout);
        $name = $this->addBlock($col, 'form-text', null, null, 1, 6, true);
        $this->addFieldConfiguration($name, 'Nom', 'Saisissez votre nom', true, false, 'Veuillez saisir votre nom.', 'lastname')->setAnonymize(true);
        $firstName = $this->addBlock($col, 'form-text', null, null, 2, 6, true);
        $this->addFieldConfiguration($firstName, 'Prénom', 'Saisissez votre prénom', true, false, 'Veuillez saisir votre prénom.', 'firstname')->setAnonymize(true);
        $email = $this->addBlock($col, 'form-email', null, null, 3, 6, true);
        $this->addFieldConfiguration($email, 'Email', 'Saisissez votre email', true, false, 'Veuillez saisir votre email.', 'email')->setAnonymize(true);
        $phone = $this->addBlock($col, 'form-phone', null, null, 4, 6, true);
        $this->addFieldConfiguration($phone, 'Téléphone', 'Saisissez votre numéro de téléphone', true, false, 'Veuillez saisir votre numéro de téléphone.', 'phone')->setAnonymize(true);
        $message = $this->addBlock($col, 'form-textarea', null, null, 5);
        $this->addFieldConfiguration($message, 'Message', 'Saisissez votre message', true, false, 'Veuillez saisir un message.', 'message');
        $gdpr = $this->addBlock($col, 'form-gdpr', null, null, 6);
        $this->addFieldConfiguration($gdpr, 'RGPD', "J'accepte que mes données soient utilisées pour me recontacter dans le cadre de cette demande.", true, true, 'Veuillez accépter.', 'gdpr');
        $submit = $this->addBlock($col, 'form-submit', null, null, 7);
        $this->addFieldConfiguration($submit, 'Envoyer', 'Saisissez votre message', true, false, null, 'submit');

        $form->setLayout($formLayout);

        $this->coreLocator->em()->persist($form);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }

        $this->layoutManager->setGridZone($formLayout);

        $col = $this->addCol($zone, 1, 8);
        $col->setPaddingRight('pe-lg');
        $block = $this->addBlock($col, 'core-action', 'form-view', $form->getId());
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');
    }

    /**
     * To set Form.
     */
    public function setForm(string $name, string $slug): FormEntity\Form
    {
        $form = new FormEntity\Form();
        $form->setWebsite($this->website);
        $form->setAdminName($name);
        $form->setSlug($slug);

        $configuration = new FormEntity\Configuration();
        $configuration->setSecurityKey($this->coreLocator->alphanumericKey(10));
        $form->setConfiguration($configuration);

        return $form;
    }

    /**
     * Add contact information to contact Page.
     *
     * @throws Exception
     */
    private function addInformation(Layout\Zone $zone): void
    {
        $col = $this->addCol($zone, 2, 4);
        $block = $this->addBlock($col, 'core-action', 'information-view');
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');
    }

    /**
     * Add Map to contact Page.
     */
    private function addMap(Layout\Layout $layout): void
    {
        $addressData = !empty($this->yamlConfiguration['addresses'][$this->locale][0]) ? $this->yamlConfiguration['addresses'][$this->locale][0] : [];

        if (!empty($addressData)) {

            $map = new MapEntity\Map();
            $map->setAdminName('Carte page de contact');
            $map->setWebsite($this->website);
            $map->setAsDefault(true);
            $map->setSlug('contact');

            $address = new MapEntity\Address();
            $address->setName($addressData['name']);
            $address->setLatitude($addressData['latitude']);
            $address->setLongitude($addressData['longitude']);
            $address->setAddress($addressData['address']);
            $address->setZipCode($addressData['zipcode']);
            $address->setCity($addressData['city']);
            $address->setDepartment($addressData['department']);
            $address->setRegion($addressData['region']);
            $address->setCountry($addressData['country']);
            $address->setGoogleMapUrl($addressData['googleMapUrl']);
            $address->setGoogleMapDirectionUrl($addressData['googleMapDirectionUrl']);

            $point = new MapEntity\Point();
            $point->setAdminName('Point principal');
            $point->setMarker('/uploads/'.$this->website->getUploadDirname().'/marker-orange.svg');
            $point->setAddress($address);

            $map->addPoint($point);

            $this->coreLocator->em()->persist($map);
            if ($this->flush) {
                $this->coreLocator->em()->flush();
            }

            $zone = $this->addZone($layout, 3, true, true);
            $col = $this->addCol($zone);
            $col->setPaddingRight('pe-0');
            $col->setPaddingLeft('ps-0');

            $block = $this->addBlock($col, 'core-action', 'map-view', $map->getId());
            $block->setPaddingRight('pe-0');
            $block->setPaddingLeft('ps-0');
        }
    }

    /**
     * Add Action.
     */
    private function addAction(Layout\Block $block, ?string $blockTypeSlug = null, ?string $actionSlug = null, ?int $actionFilter = null): void
    {
        if ($blockTypeSlug) {
            $blockType = $this->coreLocator->em()->getRepository(Layout\BlockType::class)->findOneBy(['slug' => $blockTypeSlug]);
            $block->setBlockType($blockType);

            $intlBlockTypes = ['text', 'title', 'link', 'media'];

            if (in_array($blockTypeSlug, $intlBlockTypes)) {
                $intl = 'media' === $blockTypeSlug ? new MediaRelationIntl() : new Layout\BlockIntl();
                $intl->setLocale($this->locale);
                $intl->setWebsite($this->website);
                $intl->setTitle($this->faker->text(30));

                if ('text' === $blockTypeSlug) {
                    $intl->setSubTitle($this->faker->text(20));
                    $intl->setBody('<p>'.$this->faker->text(300).'</p><p>'.$this->faker->text(200).'</p>');
                    $intl->setIntroduction($this->faker->text(100));
                }

                if ('text' === $blockTypeSlug || 'link' === $blockTypeSlug) {
                    $intl->setTargetLink('https://www.felix-creation.fr/');
                    $intl->setNewTab(true);
                }

                if ('media' !== $blockTypeSlug) {
                    $block->addIntl($intl);
                }

                if ('media' === $blockTypeSlug) {
                    $media = $this->coreLocator->em()->getRepository(Media::class)->findOneBy([
                        'website' => $this->website,
                        'category' => 'share',
                    ]);
                    $mediaRelation = new Layout\BlockMediaRelation();
                    $mediaRelation->setLocale($this->locale);
                    $mediaRelation->setMedia($media);
                    $mediaRelation->setIntl($intl);
                    $mediaRelation->setPopup(true);
                    $mediaRelation->setDownloadable(true);
                    $block->addMediaRelation($mediaRelation);
                }
            }
        }

        if ($actionSlug) {
            $action = $this->coreLocator->em()->getRepository(Layout\Action::class)->findOneBy(['slug' => $actionSlug]);
            $block->setAction($action);
        }

        if ($actionFilter) {
            $actionIntl = new Layout\ActionIntl();
            $actionIntl->setLocale($this->locale);
            $actionIntl->setBlock($block);
            $actionIntl->setBlock($block);
            $actionIntl->setActionFilter($actionFilter);
            $block->addActionIntl($actionIntl);
        }
    }

    /**
     * Add FieldConfiguration.
     */
    public function addFieldConfiguration(
        Layout\Block $block,
        string $label,
        ?string $placeholder = null,
        bool $required = true,
        bool $smallSize = false,
        ?string $error = null,
        ?string $slug = null,
    ): Layout\FieldConfiguration {
        $blockTypeSlug = $block->getBlockType()->getSlug();

        $intl = new Layout\BlockIntl();
        $intl->setLocale($this->locale);
        $intl->setWebsite($this->website);

        if ('form-gdpr' !== $blockTypeSlug) {
            $intl->setTitle($label);
            $intl->setPlaceholder($placeholder);
            $intl->setError($error);
        }

        $slug = $slug ?: Urlizer::urlize($label);
        $configuration = new Layout\FieldConfiguration();
        $configuration->setRequired($required);
        $configuration->setSmallSize($smallSize);
        $configuration->setBlock($block);
        $configuration->setSlug($slug);

        if ('form-gdpr' === $blockTypeSlug) {
            $configuration->setExpanded(true);
            $configuration->setMultiple(true);

            $valueIntl = new Layout\FieldValueIntl();
            $valueIntl->setLocale($this->locale);
            $valueIntl->setIntroduction($placeholder);
            $valueIntl->setBody('true');
            $valueIntl->setWebsite($this->website);

            $value = new Layout\FieldValue();
            $value->setAdminName($placeholder);
            $value->addIntl($valueIntl);

            $configuration->addFieldValue($value);
        }

        $block->setAdminName($label);
        $block->addIntl($intl);
        $block->setFieldConfiguration($configuration);

        $this->coreLocator->em()->persist($block);

        return $configuration;
    }

    /**
     * To set Website.
     */
    public function setWebsite(Website $website): void
    {
        $this->website = $website;
    }

    /**
     * To set locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
