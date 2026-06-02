<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Action;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\Admin\ModulePageUsage;
use App\Model\Core\WebsiteModel;
use App\Repository\Layout\PageRepository;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Core\WebsiteRuntime;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * ModuleUsageProvider.
 *
 * Resolves the front pages where a placeable module entity is used, for the admin index.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ModuleUsageProvider::class, 'key' => 'module_usage_provider'],
])]
class ModuleUsageProvider
{
    /** @var array<string, bool> */
    private array $supportsCache = [];

    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRuntime $websiteRuntime,
    ) {
    }

    /**
     * Whether the entity is a placeable module (referenced by at least one Action).
     */
    public function supports(?string $classname): bool
    {
        if (!$classname) {
            return false;
        }
        if (!array_key_exists($classname, $this->supportsCache)) {
            $count = (int) $this->coreLocator->em()->getRepository(Action::class)
                ->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->andWhere('a.entity = :entity')
                ->setParameter('entity', $classname)
                ->getQuery()
                ->enableResultCache(3600, 'action_supports_'.md5($classname))
                ->getSingleScalarResult();
            $this->supportsCache[$classname] = $count > 0;
        }

        return $this->supportsCache[$classname];
    }

    /**
     * Map of module entity id to the pages using it, capped for display.
     *
     * @return array<int, array<ModulePageUsage>>
     */
    public function forItems(string $classname, iterable $items, WebsiteModel $website, string $locale): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (is_object($item) && method_exists($item, 'getId') && $item->getId()) {
                $ids[] = $item->getId();
            }
        }
        if (!$ids) {
            return [];
        }

        /** @var PageRepository $pageRepository */
        $pageRepository = $this->coreLocator->em()->getRepository(Page::class);
        $grouped = $pageRepository->findPagesGroupedByActionFilter($website->id, $locale, $classname, $ids);

        $result = [];
        foreach ($grouped as $entityId => $pages) {
            foreach ($pages as $page) {
                $result[$entityId][] = $this->toUsage($page, $website, $locale);
            }
        }

        return $result;
    }

    private function toUsage(Page $page, WebsiteModel $website, string $locale): ModulePageUsage
    {
        $url = $this->localeUrl($page, $locale);
        $online = $url instanceof Url && $url->isOnline() && $url->getCode();
        $name = $page->getAdminName() ?: ($url instanceof Url ? $url->getCode() : null) ?: '#'.$page->getId();
        $href = null;

        if ($online) {
            $domain = $this->websiteRuntime->domain($locale, $website);
            $href = $domain ? rtrim($domain, '/').'/'.$url->getCode() : null;
        } elseif ($url instanceof Url && $url->getId()) {
            $href = $this->coreLocator->router()->generate('front_page_preview', [
                'website' => $website->id,
                'url' => $url->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new ModulePageUsage((string) $name, $href, (bool) $online);
    }

    private function localeUrl(Page $page, string $locale): ?Url
    {
        foreach ($page->getUrls() as $url) {
            if ($url->getLocale() === $locale) {
                return $url;
            }
        }

        return null;
    }
}
