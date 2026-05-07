<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\InvalidateCacheItems;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * InvalidateCacheItemsHandler.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsMessageHandler]
final readonly class InvalidateCacheItemsHandler
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function __invoke(InvalidateCacheItems $message): void
    {
        if (!$message->cacheKeys) {
            return;
        }

        $cache = $this->em->getConfiguration()->getResultCache();
        if (!$cache) {
            return;
        }

        try {
            $cache->deleteItems(array_values(array_unique($message->cacheKeys)));
        } catch (\Throwable) {
        }
    }
}
