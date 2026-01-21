<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;

/**
 * SeoInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface SeoInterface
{
    public function execute(?Url $url = null, mixed $entity = null, ?string $locale = null, bool $onlyTitle = false, ?WebsiteModel $website = null): bool|array;
    public function getRelationsModels(): array;
    public function getLocalesModels(mixed $entity, WebsiteModel $websiteModel): array;
    public function getAsCardUrl(mixed $url, mixed $entity, string $classname, bool $asObject = false, array $interface = [], array $indexPagesCodes = []);
    public function getMicrodata(WebsiteModel $websiteModel): array;
}
