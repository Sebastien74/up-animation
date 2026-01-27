<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Media as MediaEntities;
use App\Entity\Module\Newscast as NewscastEntities;
use App\Entity\Security\User;
use App\Entity\Seo\Url;
use App\Service\Content\LayoutGeneratorService;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * NewscastFixtures.
 *
 * Newscast Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => NewscastFixtures::class, 'key' => 'newscast_fixtures'],
])]
class NewscastFixtures
{
    private const int LIMIT = 15;
    private Generator $faker;
    private Website $website;
    private ?User $user;
    private string $locale = '';

    /**
     * NewscastFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LayoutGeneratorService $layoutGenerator,
    ) {
    }

    /**
     * Add News.
     *
     * @throws \Exception
     */
    public function add(Website $website, ?User $user = null): void
    {
        $this->faker = Factory::create();
        $this->website = $website;
        $this->user = $user;
        $this->locale = $website->getConfiguration()->getLocale();

        $category = $this->generateCategory();
        $this->generateTeaser($category);

        for ($i = 0; $i < self::LIMIT; ++$i) {
            $title = trim($this->faker->text(30), '.');
            $newscast = new NewscastEntities\Newscast();
            $newscast->setAdminName($title);
            $newscast->setPublicationStart(new \DateTime(sprintf('-%d days', rand(1, 100))));
            $newscast->setCategory($category);
            $newscast->setWebsite($website);
            $newscast->setPosition($i + 1);
            $newscast->setCreatedBy($this->user);
            $this->generateIntl($title, $newscast);
            $this->generateMediaRelation($newscast);
            $this->generateUrl($newscast);
        }
    }

    /**
     * Generate Category.
     */
    private function generateCategory(): NewscastEntities\Category
    {
        $category = new NewscastEntities\Category();
        $category->setAdminName('Principale');
        $category->setAsDefault(true);
        $category->setWebsite($this->website);
        $category->setSlug('main');
        $category->setCreatedBy($this->user);

        $this->coreLocator->em()->persist($category);

        $this->addListing($category);
        $this->generateLayout($category);

        $this->generateIntl('Principale', $category);

        return $category;
    }

    /**
     * Generate Listing.
     */
    private function addListing(NewscastEntities\Category $category): void
    {
        $listing = new NewscastEntities\Listing();
        $listing->addCategory($category);
        $listing->setAdminName('Principal');
        $listing->setLargeFirst(true);
        $listing->setScrollInfinite(false);
        $listing->setWebsite($this->website);
        $listing->setSlug('main');
        $listing->setCreatedBy($this->user);
        $this->coreLocator->em()->persist($listing);
    }

    /**
     * Generate MediaRelation.
     */
    private function generateMediaRelation(NewscastEntities\Newscast $newscast): void
    {
        $media = $this->coreLocator->em()->getRepository(MediaEntities\Media::class)->findOneBy([
            'website' => $this->website,
            'category' => 'share',
        ]);

        $mediaRelation = new NewscastEntities\NewscastMediaRelation();
        $mediaRelation->setLocale($this->locale);
        $mediaRelation->setMedia($media);
        $newscast->addMediaRelation($mediaRelation);
    }

    /**
     * Generate Url.
     */
    private function generateUrl(NewscastEntities\Newscast $newscast): void
    {
        $url = new Url();
        $url->setCode(Urlizer::urlize($newscast->getAdminName()));
        $url->setLocale($this->locale);
        $url->setOnline(true);
        $url->setWebsite($this->website);
        $url->setCreatedBy($this->user);
        $newscast->addUrl($url);
        $this->coreLocator->em()->persist($newscast);
    }

    /**
     * Generate Layout.
     */
    private function generateLayout(NewscastEntities\Category $category): void
    {
        $layout = $this->layoutGenerator->addLayout($this->website, [
            'adminName' => 'Fiche actualité principale',
            'slug' => 'main-category',
            'newscastcategory' => $category,
        ]);

        /** Title */
        $zoneEntitled = $this->layoutGenerator->addZone($layout, ['position' => 1, 'fullSize' => true, 'paddingTop' => 'pt-0', 'paddingBottom' => 'pb-0']);
        $col = $this->layoutGenerator->addCol($zoneEntitled, ['size' => 12, 'paddingLeft' => 'ps-0', 'paddingRight' => 'pe-0']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-title-header', 'paddingLeft' => 'ps-0', 'paddingRight' => 'pe-0']);
        /** Content */
        $zoneContent = $this->layoutGenerator->addZone($layout, ['fullSize' => false, 'paddingTop' => null, 'paddingBottom' => null]);
        /** Content column one */
        $col = $this->layoutGenerator->addCol($zoneContent, ['position' => 2, 'size' => 7, 'paddingRight' => 'pe-md']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-published-date', 'size' => 6, 'miniPcSize' => 6, 'tabletSize' => 6, 'mobileSize' => 6, 'marginBottom' => 'mb-sm']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-share', 'size' => 6, 'miniPcSize' => 6, 'tabletSize' => 6, 'mobileSize' => 6, 'alignment' => 'end', 'marginBottom' => 'mb-sm']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-intro']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-body']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-link']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-back-button', 'marginTop' => 'mt-lg', 'hideMobile' => true, 'hideTablet' => true]);
        /** Content column two */
        $col = $this->layoutGenerator->addCol($zoneContent, ['position' => 3, 'size' => 5]);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-video']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-slider']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-back-button', 'marginTop' => 'mt-md', 'hideMiniPc' => true, 'hideDesktop' => true]);
        /** Associated entities */
        $zoneAssociated = $this->layoutGenerator->addZone($layout, ['position' => 4, 'fullSize' => false, 'paddingTop' => null, 'paddingBottom' => null, 'backgroundColor' => 'bg-light', 'colToRight' => true]);
        $col = $this->layoutGenerator->addCol($zoneAssociated, ['size' => 12]);
        $block = $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-associated-entities']);
        $block->setPaddingRight('pe-0');

        $category->setLayout($layout);
    }

    /**
     * Generate Teaser.
     */
    private function generateTeaser(NewscastEntities\Category $category): void
    {
        $teaser = new NewscastEntities\Teaser();
        $teaser->setAdminName('Principal');
        $teaser->setWebsite($this->website);
        $teaser->setSlug('main');
        $teaser->setPromoteFirst(true);
        $teaser->setCreatedBy($this->user);
        $teaser->addCategory($category);
        $this->coreLocator->em()->persist($teaser);
    }

    /**
     * Generate intl.
     */
    private function generateIntl(string $title, mixed $entity): void
    {
        $intlClassname = $this->coreLocator->metadata($entity, 'intls')->targetEntity;
        $intl = new $intlClassname();

        $intl->setLocale($this->locale);
        $intl->setTitle($title);
        $intl->setIntroduction($this->faker->text(150));
        $intl->setBody($this->faker->text(600));
        $intl->setCreatedBy($this->user);
        $intl->setWebsite($this->website);
        $this->coreLocator->em()->persist($intl);

        $entity->addIntl($intl);
    }
}
