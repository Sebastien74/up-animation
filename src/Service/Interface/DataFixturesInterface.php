<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\DataFixtures as Fixtures;

/**
 * DataFixturesInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface DataFixturesInterface
{
    public function api(): Fixtures\ApiFixtures;
    public function blockType(): Fixtures\BlockTypeFixtures;
    public function color(): Fixtures\ColorFixtures;
    public function command(): Fixtures\CommandFixtures;
    public function configuration(): Fixtures\ConfigurationFixtures;
    public function defaultMedias(): Fixtures\DefaultMediasFixtures;
    public function gdpr(): Fixtures\GdprFixtures;
    public function information(): Fixtures\InformationFixtures;
    public function layout(): Fixtures\LayoutFixtures;
    public function map(): Fixtures\MapFixtures;
    public function menu(): Fixtures\MenuFixtures;
    public function newscast(): Fixtures\NewscastFixtures;
    public function catalog(): Fixtures\CatalogFixtures;
    public function newsletter(): Fixtures\NewsletterFixtures;
    public function pageDuplication(): Fixtures\PageDuplicationFixtures;
    public function page(): Fixtures\PageFixtures;
    public function security(): Fixtures\SecurityFixtures;
    public function seo(): Fixtures\SeoFixtures;
    public function thumbnail(): Fixtures\ThumbnailFixtures;
    public function transition(): Fixtures\TransitionFixtures;
    public function translations(): Fixtures\TranslationsFixtures;
    public function uploadedFile(): Fixtures\UploadedFileFixtures;
    public function website(): Fixtures\WebsiteFixtures;
}