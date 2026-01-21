<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Layout\Page;
use App\Entity\Module\Menu\Link;
use App\Entity\Module\Menu\LinkIntl;
use App\Entity\Module\Menu\LinkMediaRelation;
use App\Entity\Module\Menu\Menu;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * AddLinkManager.
 *
 * Manage admin Link Menu form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => AddLinkManager::class, 'key' => 'module_add_link_menu_form_manager'],
])]
class AddLinkManager
{
    /**
     * AddLinkManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Add Pages Link in Menu.
     *
     * @throws NonUniqueResultException
     */
    public function post(array $post, Menu $menu, string $locale, bool $multiple): void
    {
        $pages = [];
        if (!empty($post['page']) && !$multiple) {
            $pages[] = $post['page'];
        } elseif ($multiple) {
            $pages = $post;
        }

        if (!empty($pages)) {
            $repository = $this->coreLocator->em()->getRepository(Link::class);
            foreach ($pages as $page) {
                $page = $page instanceof Page ? $page : $this->getPage(intval($page));
                $parentLinks = $page->getParent() ? $repository->findByPageAndLocale($page->getParent(), $locale, $menu) : [];
                $parentLink = !empty($parentLinks[0]) ? $parentLinks[0] : null;
                $level = $parentLink instanceof Link ? $parentLink->getLevel() + 1 : 1;
                $position = count($repository->findBy([
                    'menu' => $menu,
                    'locale' => $locale,
                    'parent' => $parentLink,
                ])) + 1;
                $link = $this->addLink($page, $locale, $menu, $level, $position, $parentLink);
                $intl = $this->addIntl($link, $page, $locale);
                $this->addMediaRelation($link, $locale);
                $this->coreLocator->em()->persist($link);
                $this->coreLocator->em()->persist($intl);
                $this->coreLocator->em()->persist($menu);
                $this->coreLocator->em()->flush();
            }
            $this->coreLocator->cacheService()->clearCaches($menu);
        }
    }

    /**
     * Get Page.
     */
    private function getPage(int $pageId): ?Page
    {
        $pageRepository = $this->coreLocator->em()->getRepository(Page::class);

        return $pageRepository->find($pageId);
    }

    /**
     * Add Link.
     */
    private function addLink(Page $page, string $locale, Menu $menu, int $level, int $position, ?Link $parentLink = null): Link
    {
        $link = new Link();
        $link->setLocale($locale);
        $link->setAdminName($page->getAdminName());
        $link->setMenu($menu);
        $link->setLevel($level);
        $link->setPosition($position);
        $link->setParent($parentLink);

        return $link;
    }

    /**
     * Add intl.
     */
    private function addIntl(Link $link, Page $page, string $locale): LinkIntl
    {
        $intl = new LinkIntl();
        $intl->setLocale($locale);
        $intl->setWebsite($page->getWebsite());
        $intl->setLink($link);
        $intl->setTargetPage($page);
        $intl->setTitle($page->getAdminName());
        $link->setIntl($intl);

        return $intl;
    }

    /**
     * Add MediaRelation.
     */
    private function addMediaRelation(Link $link, string $locale): void
    {
        $mediaRelation = new LinkMediaRelation();
        $mediaRelation->setLocale($locale);
        $link->setMediaRelation($mediaRelation);
    }
}
