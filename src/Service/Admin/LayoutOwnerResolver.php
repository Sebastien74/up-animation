<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Page;
use App\Entity\Layout\Zone;
use App\Model\Admin\ModulePageUsage;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Core\WebsiteRuntime;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * LayoutOwnerResolver.
 *
 * Resolves which non-page template (news, product, category, catalog, listing, form...)
 * owns a layout, for the admin module usage column. The owner set is discovered from
 * Doctrine metadata (every entity holding a `layout` foreign key, except Page and Zone)
 * so future templates are covered automatically. One bounded query per owner type, with
 * early exit once every layout is resolved.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutOwnerResolver
{
    private const OWNERS_CACHE_KEY = 'module_layout_owners_v2';

    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRuntime $websiteRuntime,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @param array<int> $layoutIds
     *
     * @return array<int, ModulePageUsage> indexed by layout id
     */
    public function resolve(array $layoutIds, WebsiteModel $website, string $locale): array
    {
        $layoutIds = array_values(array_unique(array_map('intval', $layoutIds)));
        if (!$layoutIds) {
            return [];
        }

        $result = [];
        $domain = null;

        foreach ($this->owners() as $class => $config) {
            if (!$layoutIds) {
                break;
            }

            $buffer = [];
            $missingTitle = [];
            foreach ($this->fetchRows($class, $layoutIds, $locale, $config['linkable']) as $row) {
                $layoutId = (int) $row['layoutId'];
                if (isset($result[$layoutId]) || isset($buffer[$layoutId])) {
                    continue;
                }
                $name = $row['adminName'] ? ltrim((string) $row['adminName'], '_') : null;
                $href = null;
                $online = false;
                if ($config['linkable'] && !empty($row['code']) && !empty($row['online'])) {
                    $domain ??= $this->websiteRuntime->domain($locale, $website);
                    $href = $domain ? rtrim($domain, '/').'/'.$row['code'] : null;
                    $online = (bool) $href;
                }
                $buffer[$layoutId] = ['id' => (int) $row['id'], 'name' => $name, 'href' => $href, 'online' => $online];
                if (!$name && $config['intl']) {
                    $missingTitle[(int) $row['id']] = $layoutId;
                }
            }

            $titles = $missingTitle ? $this->fetchTitles($config['intl'], array_keys($missingTitle), $locale) : [];

            foreach ($buffer as $layoutId => $data) {
                $name = $data['name'] ?: ($titles[$data['id']] ?? null) ?: '#'.$data['id'];
                $result[$layoutId] = new ModulePageUsage((string) $name, $data['href'], $data['online']);
            }

            $layoutIds = array_values(array_diff($layoutIds, array_keys($result)));
        }

        return $result;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function fetchRows(string $class, array $layoutIds, string $locale, bool $linkable): array
    {
        $qb = $this->coreLocator->em()->getRepository($class)->createQueryBuilder('e')
            ->select('IDENTITY(e.layout) AS layoutId', 'e.id AS id', 'e.adminName AS adminName')
            ->andWhere('e.layout IN (:layoutIds)')
            ->setParameter('layoutIds', $layoutIds);

        if ($linkable) {
            $qb->leftJoin('e.urls', 'u', 'WITH', 'u.locale = :locale')
                ->addSelect('u.code AS code', 'u.online AS online')
                ->setParameter('locale', $locale);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Localized titles for the owners missing an admin name. Plain root query (no WITH on
     * the eagerly-fetched back association, which Doctrine forbids).
     *
     * @param array{class: class-string, mappedBy: string} $intl
     * @param array<int>                                    $ownerIds
     *
     * @return array<int, ?string> indexed by owner id
     */
    private function fetchTitles(array $intl, array $ownerIds, string $locale): array
    {
        $rows = $this->coreLocator->em()->getRepository($intl['class'])->createQueryBuilder('i')
            ->select(sprintf('IDENTITY(i.%s) AS ownerId', $intl['mappedBy']), 'i.title AS title')
            ->andWhere(sprintf('i.%s IN (:ownerIds)', $intl['mappedBy']))
            ->andWhere('i.locale = :locale')
            ->setParameter('ownerIds', $ownerIds)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getArrayResult();

        $titles = [];
        foreach ($rows as $row) {
            $titles[(int) $row['ownerId']] = $row['title'];
        }

        return $titles;
    }

    /**
     * Layout-owning entities, discovered once from Doctrine metadata and cached.
     *
     * @return array<class-string, array{linkable: bool, intl: ?array{class: class-string, mappedBy: string}}>
     */
    private function owners(): array
    {
        return $this->cache->get(self::OWNERS_CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(86400);

            $excluded = [Page::class, Zone::class];
            $owners = [];

            foreach ($this->coreLocator->em()->getMetadataFactory()->getAllMetadata() as $meta) {
                if ($meta->isMappedSuperclass || $meta->getReflectionClass()->isAbstract()) {
                    continue;
                }
                $class = $meta->getName();
                if (in_array($class, $excluded, true) || !$meta->hasAssociation('layout')) {
                    continue;
                }
                if (!$meta->isAssociationWithSingleJoinColumn('layout')) {
                    continue;
                }

                $intl = null;
                if ($meta->hasAssociation('intls')) {
                    $mapping = $meta->getAssociationMapping('intls');
                    if (!empty($mapping['mappedBy'])) {
                        $intl = ['class' => $mapping['targetEntity'], 'mappedBy' => $mapping['mappedBy']];
                    }
                }

                $isCategory = str_ends_with($class, '\\Category');
                $owners[$class] = [
                    'linkable' => !$isCategory && $meta->hasAssociation('urls'),
                    'intl' => $intl,
                ];
            }

            return $owners;
        });
    }
}
