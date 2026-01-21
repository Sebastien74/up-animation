<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;

/**
 * MenuServiceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface MenuServiceInterface
{
    public function all(WebsiteModel $website, ?Url $url = null): array;
}
