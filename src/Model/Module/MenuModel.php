<?php

declare(strict_types=1);

namespace App\Model\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\Menu;
use App\Entity\Seo\Url;
use App\Model\BaseModel;
use App\Model\Core\DomainModel;
use App\Model\Core\WebsiteModel;
use App\Model\IntlModel;
use App\Model\MediaModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MenuModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class MenuModel extends BaseModel
{
    /**
     * ViewModel constructor.
     */
    public function __construct(
        public readonly array $arguments,
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Menu $menu, WebsiteModel $website, CoreLocatorInterface $coreLocator, array $links = [], ?Url $url = null): self
    {
        self::setLocator($coreLocator);
        self::$coreLocator->interfaceHelper()->setInterface(Menu::class);

        $configuration = $website->configuration;
        $template = $configuration->template;
        $requestUri = self::$coreLocator->request()->getRequestUri();

        return new self(
            arguments: [
                'id' => $menu->getId(),
                'slug' => $menu->getSlug(),
                'entity' => $menu,
                'indexActive' => '' === $requestUri || '/' === $requestUri,
                'tree' => self::tree($website, $menu, $links, $url),
                'interface' => $coreLocator->interfaceHelper()->getInterface(),
                'template' => self::template($menu, $template),
                'anonymousTheme' => !$configuration->adminTheme,
                'expand' => $menu->getExpand(),
                'size' => $menu->getSize(),
                'isFooter' => $menu->isFooter(),
                'isVertical' => $menu->isVertical(),
                'isHorizontal' => !$menu->isVertical(),
                'fixedOnScroll' => $menu->isFixedOnScroll(),
                'alwaysFixed' => $menu->isAlwaysFixed(),
                'dropdownHover' => $menu->isDropdownHover(),
                'dropdownClass' => $menu->isDropdownHover() ? 'dropdown-hover' : 'dropdown-click',
                'alignment' => self::alignment($menu),
                'multiLingues' => count($configuration->onlineLocales) > 1,
                'url' => $url,
                'website' => $website,
                'configuration' => $configuration,
                'websiteTemplate' => $template,
            ]
        );
    }

    /**
     * To get tree.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function tree(
        WebsiteModel $website,
        Menu $menu,
        array $links = [],
        ?Url $url = null,
        array $defaultTree = [],
    ): array {

        $links = $links ?: self::links($menu);
        $tree = self::$coreLocator->treeService()->execute($links);
        $defaultTree = $defaultTree ?: $tree;
        $uri = self::$coreLocator->request()->getRequestUri();
        $schemeAndHttpHost = self::$coreLocator->request()->getSchemeAndHttpHost();

        $treeResponse = [];
        foreach ($tree as $key => $links) {
            foreach ($links as $keyLink => $link) {
                $intl = IntlModel::fromEntity($link, self::$coreLocator, false);
                $targetPage = $intl?->linkTargetPage;
                $path = self::linkPath($website, $intl, $url);
                $asPath = $path !== $schemeAndHttpHost;
                $active = $asPath && $path && trim($uri, '/') === trim(str_replace($schemeAndHttpHost, '', $path), '/');
                $color = $link->getColor() ? 'text-'.$link->getColor() : '';
                $btnColor = $link->getBtnColor() ? 'btn '.$link->getBtnColor() : '';
                $id = $intl->linkTargetPage && $intl->linkTargetPage->getSlug() ? 'link-'.$menu->getSlug().'-'.$intl->linkTargetPage->getSlug() : 'link-'.$menu->getSlug().'-'.$link->getSlug();
                $media = MediaModel::fromEntity($link->getMediaRelation(), self::$coreLocator);
                $pictogram = self::getContent('pictogram', $link);
                $treeResponse[$key][$keyLink] = array_merge((array) $intl, [
                    'id' => $id,
                    'entity' => $link,
                    'pictogram' => $pictogram ?: ($targetPage && $targetPage->getPictogram() ? $targetPage->getPictogram() : null),
                    'online' => $intl->linkOnline,
                    'active' => $active,
                    'level' => $link->getLevel(),
                    'asAnchor' => $intl->linkAsAnchor,
                    'dataAnchor' => $intl->linkDataAnchor,
                    'color' => $color,
                    'btnColor' => $btnColor,
                    'bgColor' => $link->getBackgroundColor() ?: '',
                    'class' => self::linkClass($id, $active, $intl->linkAsAnchor, $color, $btnColor),
                    'media' => $media->media ? $media : null,
                    'children' => !empty($defaultTree[$link->getId()]) ? self::tree($website, $menu, $defaultTree[$link->getId()], null, $defaultTree)[$link->getId()] : [],
                ]);
            }
        }

        return $treeResponse;
    }

    /**
     * To get media relations entity and locale.
     */
    private static function links(Menu $menu): array
    {
        return self::$coreLocator->em()->getRepository(Link::class)
            ->createQueryBuilder('l')
            ->innerJoin('l.intl', 'i')
            ->leftJoin('i.targetPage', 'tp')
            ->leftJoin('l.mediaRelation', 'mr')
            ->leftJoin('mr.intl', 'mi')
            ->leftJoin('mr.media', 'm')
            ->leftJoin('l.parent', 'p')
            ->andWhere('l.menu =  :menu')
            ->andWhere('i.locale =  :locale')
            ->setParameter('menu', $menu)
            ->setParameter('locale', self::$coreLocator->request()->getLocale())
            ->orderBy('l.position', 'ASC')
            ->addOrderBy('l.level', 'ASC')
            ->addSelect('i')
            ->addSelect('tp')
            ->addSelect('mr')
            ->addSelect('mi')
            ->addSelect('m')
            ->addSelect('p')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Get path.
     */
    private static function linkPath(WebsiteModel $website, IntlModel $intl, ?Url $url = null): ?string
    {
        $websiteId = $website->id;
        $targetPageWebsite = $intl->linkTargetPage?->getWebsite();
        $targetPageId = $targetPageWebsite?->getId();
        $isIndex = $intl->linkTargetPage && $intl->linkTargetPage->isAsIndex();
        $infill = $intl->linkTargetPage && $intl->linkTargetPageInfill;

        if ($infill) {
            return null;
        }

        if ($isIndex && $websiteId === $targetPageId) {
            return '/';
        }

        if ($intl->linkTargetPage && $websiteId !== $targetPageId) {
            $domain = self::domain($intl->locale, $targetPageWebsite);
            if ($domain) {
                if ($isIndex) {
                    return $domain;
                } elseif ($url) {
                    return $domain.self::$coreLocator->router()->generate('front_index', ['url' => $url->getCode()]);
                }
            }
        }

        $anchor = $intl->link && str_contains($intl->link, '#') ? Urlizer::urlize($intl->link) : '';
        if ('' !== $anchor) {
            $matches = explode('#', $anchor);
            $anchor = Urlizer::urlize('#'.end($matches));
        }
        $link = $intl->link.$anchor;
        if (!str_contains($link, self::$coreLocator->schemeAndHttpHost()) && !str_contains($link, 'http')) {
            $link = self::$coreLocator->schemeAndHttpHost().$link;
        }

        return $link ? urldecode($link) : $link;
    }

    /**
     * Get default domain name by locale.
     */
    private static function domain(string $locale, Website $website): bool|string
    {
        $protocol = $_ENV['APP_PROTOCOL'].'://';
        $configuration = $website->getConfiguration(true);
        $domains = self::$coreLocator->em()->getRepository(DomainModel::class)->findBy([
            'configuration' => $configuration,
            'asDefault' => true,
        ]);

        $defaultDomain = false;
        foreach ($domains as $domain) {
            if ($domain->getLocale() === $locale) {
                return $protocol.$domain->getName();
            }
            if ($domain->getLocale() === $configuration->getLocale()) {
                $defaultDomain = $protocol.$domain->getName();
            }
        }

        return $defaultDomain;
    }

    /**
     * Get link class.
     */
    private static function linkClass(
        string $id,
        bool $active,
        bool $asAnchor,
        ?string $color = null,
        ?string $btnColor = null,
    ): string {
        $class = 'track-'.$id;
        $class = $active ? $class.' active' : $class;
        $class = $asAnchor ? $class.' as-anchor' : $class;
        $class = $color ? $class.' '.$color : $class;

        return $btnColor ? $class.' '.$btnColor : $class;
    }

    /**
     * Get template.
     */
    private static function template(Menu $menu, string $template): string
    {
        $filesystem = new Filesystem();
        $default = 'front/'.$template.'/actions/menu/'.$menu->getTemplate().'.html.twig';
        $custom = 'front/'.$template.'/actions/menu/'.$menu->getSlug().'.html.twig';
        if ($menu->isMain()) {
            $custom = 'front/'.$template.'/actions/menu/'.$menu->getTemplate().'.html.twig';
        }
        if ($menu->isVertical()) {
            $custom = 'front/'.$template.'/actions/menu/lateral.html.twig';
        }
        $customDirname = self::$coreLocator->projectDir().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $custom);

        return $filesystem->exists($customDirname) ? $custom : $default;
    }

    /**
     * Get template.
     */
    private static function alignment(Menu $menu): string
    {
        $alignment = 'mx-auto';
        $alignment = 'start' === $menu->getAlignment() ? 'me-auto' : $alignment;

        return 'end' === $menu->getAlignment() ? 'ms-auto' : $alignment;
    }
}
