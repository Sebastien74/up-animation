<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Model\EntityModel;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * MenuRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MenuRuntime implements RuntimeExtensionInterface
{
    private const bool SAME_LEVEL = false;

    /**
     * MenuRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LayoutRuntime $layoutRuntime,
    ) {
    }

    /**
     * Get sub-navigation of page.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|\ReflectionException|QueryException
     */
    public function subNavigation(mixed $page = null, ?string $locale = null): array
    {
        $response = [];
        $page = $page instanceof ViewModel ? $page->entity : (is_object($page) && property_exists($page, 'entity') ? $page->entity : null);
        if ($page instanceof Page) {
            $model = ViewModel::fromEntity($page, $this->coreLocator, ['disabledIntl' => true, 'disabledMedias' => true, 'disabledCategory' => true]);
            $seo = $this->coreLocator->seoService()->execute($model->urlEntity, null, $this->coreLocator->locale(), true);
            $response['currentPage'] = array_merge((array) $model, [
                'level' => $page->getLevel(),
                'title' => !empty($seo['titleH1']) ? $seo['titleH1'] : $seo['title'],
                'active' => $this->coreLocator->request()->getUri() === $model->url,
                'seo' => $seo,
            ]);
            $locale = !$locale ? $this->coreLocator->request()->getLocale() : $locale;
            $subNavigation = $this->coreLocator->em()->getRepository(Page::class)->findOnlineAndLocaleByParent($page, $locale, self::SAME_LEVEL);
            if (!$subNavigation && $page->getParent() instanceof Page) {
                $subNavigation = $this->coreLocator->em()->getRepository(Page::class)->findOnlineAndLocaleByParent($page->getParent(), $locale, self::SAME_LEVEL);
            }
            foreach ($subNavigation as $subNavigationItem) {
                $title = $this->layoutRuntime->mainLayoutTitle($subNavigationItem->getLayout());
                $model = ViewModel::fromEntity($subNavigationItem, $this->coreLocator, ['disabledIntl' => true, 'disabledMedias' => true]);
                $seo = $this->coreLocator->seoService()->execute($model->urlEntity, null, $this->coreLocator->locale(), true);
                $matches = $this->coreLocator->request()->getUri() ? explode('?', $this->coreLocator->request()->getUri()) : [];
                $uri = !empty($matches[0]) ? $matches[0] : null;
                $response['items'][] = array_merge((array) $model, [
                    'level' => $subNavigationItem->getLevel(),
                    'title' => !empty($title) ? $title : (!empty($seo['titleH1']) ? $seo['titleH1'] : $seo['title']),
                    'active' => $uri === $model->url,
                    'seo' => $seo,
                ]);
            }
        }

        return (!empty($response['items']) && count($response['items']) > 1) || self::SAME_LEVEL ? $response : [];
    }

    /**
     * Get zones navigation of page.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public function zonesNavigation(Layout $layout): array
    {
        $zones = [];
        foreach ($layout->getZones() as $zone) {
            $model = EntityModel::fromEntity($zone, $this->coreLocator, ['disabledMedias' => true, 'disabledLayout' => true])->response;
            if ($model->intl->title) {
                $zones[] = $model;
            }
        }

        return $zones;
    }
}
