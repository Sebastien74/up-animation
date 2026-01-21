<?php

declare(strict_types=1);

namespace App\Security\Interface;

use App\Model\Core\WebsiteModel;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * UserCheckerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface UserCheckerInterface
{
    public function execute(RequestEvent $event, ?WebsiteModel $website = null): void;
}
