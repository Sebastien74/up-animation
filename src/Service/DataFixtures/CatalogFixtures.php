<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog as CatalogEntities;
use App\Entity\Security\User;
use App\Service\Content\LayoutGeneratorService;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * CatalogFixtures.
 *
 * Catalog Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CatalogFixtures::class, 'key' => 'catalog_fixtures'],
])]
readonly class CatalogFixtures
{
    /**
     * CatalogFixtures constructor.
     */
    public function __construct(
        private CoreLocatorInterface   $coreLocator,
        private LayoutGeneratorService $layoutGenerator,
    ) {
    }

    /**
     * Add Product.
     *
     * @throws Exception
     */
    public function add(Website $website, ?User $user = null): void
    {
        $catalog = new CatalogEntities\Catalog();
        $catalog->setAdminName('Animation');
        $catalog->setSlug('animation');
        $catalog->setWebsite($website);
        $catalog->setCreatedBy($user);
        $this->generateLayout($catalog);
        $this->coreLocator->em()->persist($catalog);

        $this->generateTeaser($catalog);

        $catalog = new CatalogEntities\Catalog();
        $catalog->setAdminName('Location');
        $catalog->setSlug('location');
        $catalog->setWebsite($website);
        $catalog->setCreatedBy($user);
        $catalog->setPosition(2);
        $this->generateLayout($catalog);
        $this->coreLocator->em()->persist($catalog);

        $this->generateTeaser($catalog);

        $this->coreLocator->em()->flush();
    }

    /**
     * Generate Layout.
     */
    private function generateLayout(CatalogEntities\Catalog $catalog): void
    {
        $layout = $this->layoutGenerator->addLayout($catalog->getWebsite(), [
            'adminName' => 'Fiche produit '.$catalog->getAdminName(),
            'slug' => $catalog->getSlug(),
            'catalog' => $catalog,
        ]);

        /** Title */
        $zoneEntitled = $this->layoutGenerator->addZone($layout, ['position' => 1, 'fullSize' => true, 'paddingTop' => 'pt-0', 'paddingBottom' => 'pb-0']);
        $col = $this->layoutGenerator->addCol($zoneEntitled, ['size' => 12, 'paddingRight' => 'pe-0', 'paddingLeft' => 'ps-0']);
        $block = $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-title-header']);
        $block->setPaddingRight('pe-0');
        $block->setPaddingLeft('ps-0');

        /** Content */
        $zoneContent = $this->layoutGenerator->addZone($layout, ['position' => 2, 'fullSize' => false, 'paddingTop' => null, 'paddingBottom' => null]);
        /** Content column one */
        $col = $this->layoutGenerator->addCol($zoneContent, ['size' => 6, 'paddingRight' => 'pe-md']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-published-date', 'size' => 6, 'miniPcSize' => 6, 'tabletSize' => 6, 'mobileSize' => 6, 'marginBottom' => 'mb-sm']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-share', 'size' => 6, 'miniPcSize' => 6, 'tabletSize' => 6, 'mobileSize' => 6, 'alignment' => 'end', 'marginBottom' => 'mb-sm']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-intro']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-body']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-link']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-back-button', 'marginTop' => 'mt-lg', 'hideMobile' => true, 'hideTablet' => true]);
        /** Content column two */
        $col = $this->layoutGenerator->addCol($zoneContent, ['size' => 6]);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-video']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-slider']);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-back-button', 'marginTop' => 'mt-md', 'hideMiniPc' => true, 'hideDesktop' => true]);
        /** Associated entities */
        $zoneAssociated = $this->layoutGenerator->addZone($layout, ['position' => 3, 'fullSize' => false, 'paddingTop' => null, 'paddingBottom' => null, 'backgroundColor' => 'bg-light']);
        $zoneAssociated->setFullSize(true);
        $zoneAssociated->setEndAlign(true);
        $col = $this->layoutGenerator->addCol($zoneAssociated, ['size' => 12]);
        $block = $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-associated-entities']);
        $block->setPaddingRight('pe-0');

        $catalog->setLayout($layout);
    }

    /**
     * Generate Teaser.
     */
    private function generateTeaser(CatalogEntities\Catalog $catalog): void
    {
        $teaser = new CatalogEntities\Teaser();
        $teaser->setAdminName('Principal');
        $teaser->setWebsite($catalog->getWebsite());
        $teaser->setSlug('main');
        $teaser->setPromoteFirst(true);
        $teaser->setCreatedBy($catalog->getCreatedBy());
        $teaser->addCatalog($catalog);
        $this->coreLocator->em()->persist($teaser);
    }
}
