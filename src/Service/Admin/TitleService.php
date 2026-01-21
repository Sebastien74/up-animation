<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Block;
use App\Entity\Layout\BlockType;
use App\Entity\Media\MediaRelationIntl;
use App\Model\Core\WebsiteModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * TitleService.
 *
 * Manage block title
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => TitleService::class, 'key' => 'title_service'],
])]
class TitleService
{
    private string $defaultLocale = '';

    /**
     * TitleService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Execute service.
     */
    public function execute(WebsiteModel $website, mixed $entity): void
    {
        $this->defaultLocale = $website->configuration->locale;
        if (is_object($entity) && method_exists($entity, 'getIntls')) {
            $titleForce = $this->getTitleForce($entity);
            foreach ($entity->getIntls() as $intl) {
                $this->titleForce($titleForce, $intl);
            }
        } elseif (is_object($entity) && method_exists($entity, 'getIntl')) {
            $this->titleForce(2, $entity->getIntl());
        }
    }

    /**
     * Get title force.
     */
    private function getTitleForce($entity): int
    {
        $defaultTitleForce = null;
        $titleForce = $entity instanceof Block && $entity->getBlockType() instanceof BlockType && 'title-header' === $entity->getBlockType()->getSlug() ? 1 : 2;

        foreach ($entity->getIntls() as $intl) {
            if ($intl->getLocale() === $this->defaultLocale && $intl->getTitleForce()) {
                $defaultTitleForce = $intl->getTitleForce();
            } elseif ($intl->getTitleForce()) {
                $titleForce = $intl->getTitleForce();
            }
        }

        return $defaultTitleForce ?: $titleForce;
    }

    /**
     * Set title force.
     */
    private function titleForce(int $titleForce, mixed $intl = null): void
    {
        if ($intl && method_exists($intl, 'getTitleForce') && !$intl->getTitleForce()) {
            $titleForce = $intl instanceof MediaRelationIntl ? 3 : $titleForce;
            $intl->setTitleForce($titleForce);
            $this->entityManager->persist($intl);
        }
    }
}
