<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * BreadcrumbRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BreadcrumbRuntime implements RuntimeExtensionInterface
{
    private const bool DISPLAY_HOME = true;
    private const bool DISPLAY_CURRENT = true;
    private const bool WITH_FILL_PAGE = true;

    /**
     * BreadcrumbRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CoreRuntime $coreRuntime,
    ) {
    }

    /**
     * Generate breadcrumb.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|InvalidArgumentException|ReflectionException
     */
    public function breadcrumb(mixed $page = null, ?string $currentTitle = null, $currentEntity = null, array $options = []): ?array
    {
        if (!$page && !$currentTitle) {
            return null;
        }

        $website = $this->coreLocator->website();
        $pageRepository = $this->coreLocator->em()->getRepository(Page::class);
        $page = $page instanceof ViewModel ? $page->entity : $page;
        $pages = isset($options['pages']) && is_iterable($options['pages']) ? $options['pages'] : false;
        $breadcrumbs = [];
        $disableModelArgs = ['disabledMedias' => true, 'disabledCategories' => true, 'disabledCategory' => true];

        if (self::DISPLAY_HOME) {
            $breadcrumbs[] = [
                'title' => $this->coreLocator->translator()->trans('Accueil', [], 'front'),
                'url' => $this->coreLocator->router()->generate('front_index', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        if (is_iterable($pages)) {
            foreach ($pages as $page) {
                $breadcrumbs[] = ['title' => $page['title'], 'url' => $page['url'] ?? false, 'entity' => null, 'inFill' => false, 'inFillUrl' => false];
            }
        } else {

            $page = $page && property_exists($page, 'entity') ? $page->entity : $page;
            if ($page && 'error' !== $page->getSlug()) {
                $indexPage = $this->indexPage($currentEntity);
                $page = $indexPage ?: $page;
                $items = $this->getBreadcrumb($page, []);
                foreach ($items as $item) {
                    if (!$item->isAsIndex()) {
                        $item = ViewModel::fromEntity($item, $this->coreLocator, $disableModelArgs);
                        $seo = $this->coreLocator->seoService()->execute($item->urlEntity, $item, null, true, $website, ['menuLink' => true]);
                        if ($seo) {
                            $itemTitle = $seo['breadcrumb'] ?: ($seo['titleH1'] ?: $seo['title']);
                            $inFill = $item->entity instanceof Page && $item->entity->isInFill();
                            if ($item->entity instanceof Page && $item->entity->isInfill()) {
                                $children = $pageRepository->findOneBy(['website' => $website->entity, 'parent' => $item->entity, 'position' => 1], ['position' => 'ASC']);
                                $item = $children instanceof Page ? ViewModel::fromEntity($children, $this->coreLocator, $disableModelArgs) : $item;
                            }
                            if (!empty($itemTitle)) {
                                $breadcrumbs[] = ['title' => $itemTitle, 'url' => !$inFill ? $item->url : false, 'entity' => $item, 'inFill' => $inFill, 'inFillUrl' => $inFill ? $item->url : false];
                            }
                        }
                    }
                }
            }

            if (!empty($options['parent'])) {
                $url = !empty($options['parent']['url']) ? $options['parent']['url']
                    : (!empty($options['parent']['route']) ? $this->coreLocator->router()->generate($options['parent']['route'], [], UrlGeneratorInterface::ABSOLUTE_URL) : null);
                $url = !str_contains($url, $this->coreLocator->schemeAndHttpHost()) ? $this->coreLocator->schemeAndHttpHost().$url : $url;
                $breadcrumbs[] = ['title' => $options['parent']['title'], 'url' => $url, 'inFill' => false];
            }

            if ($currentTitle && $page && $currentEntity && $page->getId() !== $currentEntity->id) {
                $breadcrumbs[] = ['title' => $currentTitle, 'url' => false, 'inFill' => false];
            }

            $breadcrumbs = $this->setBreadcrumbVars($breadcrumbs);
        }

        if (!self::DISPLAY_CURRENT && count($breadcrumbs) > 1) {
            unset($breadcrumbs[array_key_last($breadcrumbs)]);
        }

        return count($breadcrumbs) > 1 || (self::DISPLAY_HOME && count($breadcrumbs) >= 1) ? $breadcrumbs : [];
    }

    /**
     * Generate breadcrumb tree.
     */
    public function getBreadcrumb(mixed $entity, $items): array
    {
        if ($entity instanceof Page) {
            $level = $entity->getLevel();
            if ((!self::WITH_FILL_PAGE && !$entity->isInfill()) || self::WITH_FILL_PAGE) {
                $items[$level] = $entity;
                $indexPage = $this->indexPage($entity);
                $parent = $indexPage ?: $entity->getParent();
            }
            if (!empty($parent) && $level > 1) {
                return $this->getBreadcrumb($parent, $items);
            }
            ksort($items);
        }

        return $items;
    }

    /**
     * Get index Page.
     */
    public function indexPage(mixed $currentEntity): ?Page
    {
        if (method_exists($currentEntity, 'getUrls')) {
            foreach ($currentEntity->getUrls() as $url) {
                /** @var Url $url */
                if ($url->getLocale() === $this->coreLocator->request()->getLocale() && $url->getIndexPage()) {
                    return $url->getIndexPage();
                }
            }
        }

        return null;
    }

    /**
     * To set finished variables.
     */
    public function setBreadcrumbVars(array $breadcrumbs): array
    {
        $urls = [];
        $currentUri = $this->coreLocator->request()->getUri();
        foreach ($breadcrumbs as $key => $item) {
            $breadcrumbs[$key]['url'] = is_string($item['url']) ? trim($item['url'], '/') : $item['url'];
            $breadcrumbs[$key]['title'] = $this->coreRuntime->truncate($item['title'], 35);
            if ($breadcrumbs[$key]['url'] === $currentUri) {
                $breadcrumbs[$key]['url'] = false;
            }
            if (in_array($breadcrumbs[$key]['url'], $urls) && is_string($breadcrumbs[$key]['url'])) {
                unset($breadcrumbs[$key]);
            } else {
                $urls[] = $breadcrumbs[$key]['url'];
            }
        }

        foreach ($breadcrumbs as $key => $item) {
            if(!empty($item['inFillUrl']) && !$item['url']) {
                $breadcrumbs[$key]['url'] = $item['inFillUrl'];
            }
        }

        return $breadcrumbs;
    }
}
