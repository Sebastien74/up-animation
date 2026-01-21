<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Media as MediaEntities;
use App\Entity\Module\Catalog as CatalogEntities;
use App\Entity\Security\User;
use App\Entity\Seo\Url;
use App\Model\Module\ProductModel;
use App\Service\Content\LayoutGeneratorService;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Finder\Finder;

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
class CatalogFixtures
{
    private const int LIMIT = 15;
    private Generator $faker;
    private Website $website;
    private ?User $user;
    private string $locale = '';

    /**
     * CatalogFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LayoutGeneratorService $layoutGenerator,
    ) {
    }

    /**
     * Add Product.
     *
     * @throws \Exception
     */
    public function add(Website $website, ?User $user = null): void
    {
        if (count($this->coreLocator->em()->getRepository(CatalogEntities\Product::class)->findBy(['website' => $website])) > 0) {
            return;
        }

        $this->faker = Factory::create();
        $this->website = $website;
        $this->user = $user;
        $this->locale = $website->getConfiguration()->getLocale();

        if (empty($this->coreLocator->em()->getRepository(CatalogEntities\Feature::class)->findAll())) {
            $finder = Finder::create();
            $iconsDirname = $this->coreLocator->projectDir().'\public\medias\icons\light';
            $iconsDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $iconsDirname);
            $icons = [];
            foreach ($finder->in($iconsDirname) as $file) {
                $icons[] = $file->getFilename();
            }
            for ($i = 1; $i <= 5; ++$i) {
                $title = trim($this->faker->text(15), '.');
                $feature = new CatalogEntities\Feature();
                $feature->setAdminName($title);
                $feature->setSlug(Urlizer::urlize($title));
                $feature->setWebsite($website);
                $feature->setPosition($i);
                $feature->setCreatedBy($this->user);
                $feature->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->generateIntl($title, $feature);
                $this->generateMediaRelation($feature);
                for ($j = 1; $j <= 30; ++$j) {
                    $icon = $icons[array_rand($icons)];
                    $title = trim($this->faker->text(15), '.');
                    $featureValue = new CatalogEntities\FeatureValue();
                    $featureValue->setAdminName($title);
                    $featureValue->setSlug(Urlizer::urlize($title));
                    $featureValue->setWebsite($website);
                    $featureValue->setPosition($j);
                    $featureValue->setIconClass('/medias/icons/light/'.$icon);
                    $featureValue->setCreatedBy($this->user);
                    $featureValue->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $feature->addValue($featureValue);
                    $this->generateIntl($title, $featureValue);
                    $this->generateMediaRelation($featureValue);
                    $this->coreLocator->em()->persist($featureValue);
                }
                $this->coreLocator->em()->persist($feature);
                $this->coreLocator->em()->flush();
            }
            $this->coreLocator->em()->flush();
        }

        $features = $this->coreLocator->em()->getRepository(CatalogEntities\FeatureValue::class)->findAll();
        $catalog = $this->generateCatalog();
        $this->generateTeaser($catalog);

        for ($i = 1; $i <= self::LIMIT; ++$i) {
            $title = trim($this->faker->text(30), '.');
            $product = new CatalogEntities\Product();
            $product->setAdminName($title);
            $product->setPublicationStart(new \DateTime(sprintf('-%d days', rand(1, 100))));
            $product->setCatalog($catalog);
            $product->setWebsite($website);
            $product->setPosition($i);
            $product->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $product->setCreatedBy($this->user);
            $this->generateIntl($title, $product);
            $this->generateMediaRelation($product);
            $this->generateUrl($product);
            $valuesKeys = array_rand($features, 45);
            foreach ($valuesKeys as $key => $valuesKey) {
                $value = $features[$valuesKey];
                $valueProduct = new CatalogEntities\FeatureValueProduct();
                $valueProduct->setValue($value);
                $valueProduct->setFeature($value->getCatalogfeature());
                $valueProduct->setProduct($product);
                $valueProduct->setCreatedBy($this->user);
                $valueProduct->setPosition($key + 1);
                $valueProduct->setFeaturePosition($key + 1);
                $valueProduct->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $product->addValue($valueProduct);
            }

            ProductModel::fromEntity($product, $this->coreLocator);
            $this->coreLocator->em()->persist($product);
        }

        $this->coreLocator->em()->flush();
    }

    /**
     * Generate Category.
     */
    private function generateCatalog(): CatalogEntities\Catalog
    {
        $catalog = new CatalogEntities\Catalog();
        $catalog->setAdminName('Principal');
        $catalog->setWebsite($this->website);
        $catalog->setCreatedBy($this->user);
        $this->coreLocator->em()->persist($catalog);
        $this->addListing($catalog);
        $this->generateLayout($catalog);

        return $catalog;
    }

    /**
     * Generate Listing.
     */
    private function addListing(CatalogEntities\Catalog $catalog): void
    {
        $listing = new CatalogEntities\Listing();
        $listing->addCatalog($catalog);
        $listing->setAdminName('Principal');
        $listing->setWebsite($this->website);
        $listing->setSlug('main');
        $listing->setCreatedBy($this->user);
        $this->coreLocator->em()->persist($listing);
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
        $intl->setWebsite($this->website);
        $intl->setIntroduction($this->faker->text(150));
        $intl->setBody($this->faker->text(600));
        $intl->setCreatedBy($this->user);
        $this->coreLocator->em()->persist($intl);
        $entity->addIntl($intl);
    }

    /**
     * Generate MediaRelation.
     */
    private function generateMediaRelation(mixed $entity): void
    {
        $media = $this->coreLocator->em()->getRepository(MediaEntities\Media::class)->findOneBy([
            'website' => $this->website,
            'category' => 'share',
        ]);

        $mediaClassname = $this->coreLocator->metadata($entity, 'mediaRelations')->targetEntity;
        $mediaRelation = new $mediaClassname();
        $mediaRelation->setLocale($this->locale);
        $mediaRelation->setMedia($media);
        $entity->addMediaRelation($mediaRelation);
    }

    /**
     * Generate Url.
     */
    private function generateUrl(CatalogEntities\Product $product): void
    {
        $url = new Url();
        $url->setCode(Urlizer::urlize($product->getAdminName()));
        $url->setLocale($this->locale);
        $url->setOnline(true);
        $url->setWebsite($this->website);
        $url->setCreatedBy($this->user);
        $product->addUrl($url);
        $this->coreLocator->em()->persist($product);
    }

    /**
     * Generate Layout.
     */
    private function generateLayout(CatalogEntities\Catalog $catalog): void
    {
        $layout = $this->layoutGenerator->addLayout($this->website, [
            'adminName' => 'Fiche produit principale',
            'slug' => 'main-catalog',
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
        $col = $this->layoutGenerator->addCol($zoneAssociated, ['size' => 12]);
        $this->layoutGenerator->addBlock($col, ['blockType' => 'layout-associated-entities']);

        $catalog->setLayout($layout);
    }

    /**
     * Generate Teaser.
     */
    private function generateTeaser(CatalogEntities\Catalog $catalog): void
    {
        $teaser = new CatalogEntities\Teaser();
        $teaser->setAdminName('Principal');
        $teaser->setWebsite($this->website);
        $teaser->setSlug('main');
        $teaser->setPromoteFirst(true);
        $teaser->setCreatedBy($this->user);
        $teaser->addCatalog($catalog);
        $this->coreLocator->em()->persist($teaser);
    }
}
