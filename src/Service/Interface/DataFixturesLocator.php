<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\DataFixtures as Fixtures;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DataFixturesLocator implements DataFixturesInterface
{
    /**
     * FrontFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Fixtures\ApiFixtures::class, indexAttribute: 'key')] protected ServiceLocator $apiLocator,
        #[AutowireLocator(Fixtures\BlockTypeFixtures::class, indexAttribute: 'key')] protected ServiceLocator $blockTypeLocator,
        #[AutowireLocator(Fixtures\ColorFixtures::class, indexAttribute: 'key')] protected ServiceLocator $colorLocator,
        #[AutowireLocator(Fixtures\CommandFixtures::class, indexAttribute: 'key')] protected ServiceLocator $commandLocator,
        #[AutowireLocator(Fixtures\ConfigurationFixtures::class, indexAttribute: 'key')] protected ServiceLocator $configurationLocator,
        #[AutowireLocator(Fixtures\DefaultMediasFixtures::class, indexAttribute: 'key')] protected ServiceLocator $defaultMediaLocator,
        #[AutowireLocator(Fixtures\GdprFixtures::class, indexAttribute: 'key')] protected ServiceLocator $gdprLocator,
        #[AutowireLocator(Fixtures\InformationFixtures::class, indexAttribute: 'key')] protected ServiceLocator $infoLocator,
        #[AutowireLocator(Fixtures\LayoutFixtures::class, indexAttribute: 'key')] protected ServiceLocator $layoutLocator,
        #[AutowireLocator(Fixtures\MapFixtures::class, indexAttribute: 'key')] protected ServiceLocator $mapLocator,
        #[AutowireLocator(Fixtures\MenuFixtures::class, indexAttribute: 'key')] protected ServiceLocator $menuLocator,
        #[AutowireLocator(Fixtures\NewscastFixtures::class, indexAttribute: 'key')] protected ServiceLocator $newscastLocator,
        #[AutowireLocator(Fixtures\CatalogFixtures::class, indexAttribute: 'key')] protected ServiceLocator $catalogLocator,
        #[AutowireLocator(Fixtures\NewsletterFixtures::class, indexAttribute: 'key')] protected ServiceLocator $newsletterLocator,
        #[AutowireLocator(Fixtures\PageDuplicationFixtures::class, indexAttribute: 'key')] protected ServiceLocator $pageDuplicationLocator,
        #[AutowireLocator(Fixtures\PageFixtures::class, indexAttribute: 'key')] protected ServiceLocator $pageLocator,
        #[AutowireLocator(Fixtures\SecurityFixtures::class, indexAttribute: 'key')] protected ServiceLocator $securityLocator,
        #[AutowireLocator(Fixtures\SeoFixtures::class, indexAttribute: 'key')] protected ServiceLocator $seoLocator,
        #[AutowireLocator(Fixtures\ThumbnailFixtures::class, indexAttribute: 'key')] protected ServiceLocator $thumbnailLocator,
        #[AutowireLocator(Fixtures\TransitionFixtures::class, indexAttribute: 'key')] protected ServiceLocator $transitionLocator,
        #[AutowireLocator(Fixtures\TranslationsFixtures::class, indexAttribute: 'key')] protected ServiceLocator $translationLocator,
        #[AutowireLocator(Fixtures\UploadedFileFixtures::class, indexAttribute: 'key')] protected ServiceLocator $uploadFileLocator,
        #[AutowireLocator(Fixtures\WebsiteFixtures::class, indexAttribute: 'key')] protected ServiceLocator $websiteFileLocator,
    ) {
    }

    /**
     * To get ApiFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function api(): Fixtures\ApiFixtures
    {
        return $this->apiLocator->get('api_fixtures');
    }

    /**
     * To get BlockTypeFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function blockType(): Fixtures\BlockTypeFixtures
    {
        return $this->blockTypeLocator->get('block_type_fixtures');
    }

    /**
     * To get ColorFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function color(): Fixtures\ColorFixtures
    {
        return $this->colorLocator->get('color_fixtures');
    }

    /**
     * To get CommandFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function command(): Fixtures\CommandFixtures
    {
        return $this->commandLocator->get('command_fixtures');
    }

    /**
     * To get ConfigurationFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function configuration(): Fixtures\ConfigurationFixtures
    {
        return $this->configurationLocator->get('config_fixtures');
    }

    /**
     * To get DefaultMediasFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function defaultMedias(): Fixtures\DefaultMediasFixtures
    {
        return $this->defaultMediaLocator->get('default_medias_fixtures');
    }

    /**
     * To get GdprFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function gdpr(): Fixtures\GdprFixtures
    {
        return $this->gdprLocator->get('gdpr_fixtures');
    }

    /**
     * To get InformationFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function information(): Fixtures\InformationFixtures
    {
        return $this->infoLocator->get('information_fixtures');
    }

    /**
     * To get LayoutFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function layout(): Fixtures\LayoutFixtures
    {
        return $this->layoutLocator->get('layout_fixtures');
    }

    /**
     * To get MapFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function map(): Fixtures\MapFixtures
    {
        return $this->mapLocator->get('map_fixtures');
    }

    /**
     * To get MenuFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function menu(): Fixtures\MenuFixtures
    {
        return $this->menuLocator->get('menu_fixtures');
    }

    /**
     * To get NewscastFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function newscast(): Fixtures\NewscastFixtures
    {
        return $this->newscastLocator->get('newscast_fixtures');
    }

    /**
     * To get CatalogFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function catalog(): Fixtures\CatalogFixtures
    {
        return $this->catalogLocator->get('catalog_fixtures');
    }

    /**
     * To get NewsletterFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function newsletter(): Fixtures\NewsletterFixtures
    {
        return $this->newsletterLocator->get('newsletter_fixtures');
    }

    /**
     * To get PageDuplicationFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function pageDuplication(): Fixtures\PageDuplicationFixtures
    {
        return $this->pageDuplicationLocator->get('page_duplication_fixtures');
    }

    /**
     * To get PageFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function page(): Fixtures\PageFixtures
    {
        return $this->pageLocator->get('page_fixtures');
    }

    /**
     * To get SecurityFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function security(): Fixtures\SecurityFixtures
    {
        return $this->securityLocator->get('security_fixtures');
    }

    /**
     * To get SeoFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function seo(): Fixtures\SeoFixtures
    {
        return $this->seoLocator->get('seo_fixtures');
    }

    /**
     * To get ThumbnailFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function thumbnail(): Fixtures\ThumbnailFixtures
    {
        return $this->thumbnailLocator->get('thumbnail_fixtures');
    }

    /**
     * To get TransitionFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function transition(): Fixtures\TransitionFixtures
    {
        return $this->transitionLocator->get('transition_fixtures');
    }

    /**
     * To get TranslationsFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function translations(): Fixtures\TranslationsFixtures
    {
        return $this->translationLocator->get('translations_fixtures');
    }

    /**
     * To get UploadedFileFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function uploadedFile(): Fixtures\UploadedFileFixtures
    {
        return $this->uploadFileLocator->get('uploaded_file_fixtures');
    }

    /**
     * To get WebsiteFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function website(): Fixtures\WebsiteFixtures
    {
        return $this->websiteFileLocator->get('website_fixtures');
    }
}
