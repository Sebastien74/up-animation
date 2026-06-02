<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Action;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Model\Admin\ModulePageUsage;
use App\Model\Core\WebsiteModel;
use App\Repository\Layout\LayoutRepository;
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
        private readonly LayoutOwnerResolver $layoutOwnerResolver,
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
        $domain = null;
        foreach ($grouped as $entityId => $pages) {
            foreach ($pages as $page) {
                $result[$entityId][] = $this->toUsage($page, $website, $locale, $domain);
            }
        }

        $this->appendNonPageUsages($result, $classname, $ids, $website, $locale);

        return $result;
    }

    /**
     * Append usages from non-page templates (news, product, category, listing...).
     *
     * @param array<int, array<ModulePageUsage>> $result
     * @param array<int>                          $ids
     */
    private function appendNonPageUsages(array &$result, string $classname, array $ids, WebsiteModel $website, string $locale): void
    {
        /** @var LayoutRepository $layoutRepository */
        $layoutRepository = $this->coreLocator->em()->getRepository(Layout::class);
        $grouped = $layoutRepository->findNonPageLayoutIdsGroupedByActionFilter($website->id, $locale, $classname, $ids);
        if (!$grouped) {
            return;
        }

        $allLayoutIds = array_merge(...array_values($grouped));
        $usages = $this->layoutOwnerResolver->resolve($allLayoutIds, $website, $locale);
        if (!$usages) {
            return;
        }

        foreach ($grouped as $entityId => $layoutIds) {
            foreach ($layoutIds as $layoutId) {
                if (isset($usages[$layoutId])) {
                    $result[$entityId][] = $usages[$layoutId];
                }
            }
        }
    }

    /**
     * @param array{pageId: int, adminName: ?string, urlId: ?int, code: ?string, online: bool} $page
     */
    private function toUsage(array $page, WebsiteModel $website, string $locale, bool|string|null &$domain): ModulePageUsage
    {
        $online = !empty($page['code']) && $page['online'];
        $name = ($page['adminName'] ? ltrim($page['adminName'], '_') : null) ?: $page['code'] ?: '#'.$page['pageId'];
        $href = null;

        if ($online) {
            $domain ??= $this->websiteRuntime->domain($locale, $website);
            $href = $domain ? rtrim($domain, '/').'/'.$page['code'] : null;
        } elseif ($page['urlId']) {
            $href = $this->coreLocator->router()->generate('front_page_preview', [
                'website' => $website->id,
                'url' => $page['urlId'],
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new ModulePageUsage((string) $name, $href, $online);
    }
}
