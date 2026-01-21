<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Core\Website;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * WebsiteUpdatedEvent.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteUpdatedEvent extends Event
{
    public const string NAME = 'website.updated';

    /**
     * WebsiteUpdatedEvent constructor.
     */
    public function __construct(protected readonly Website $website)
    {
    }

    /**
     * Get WebsiteModel.
     */
    public function getWebsite(): Website
    {
        return $this->website;
    }
}
