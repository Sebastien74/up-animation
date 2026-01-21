<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Module\Menu as MenuEntities;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * MenuFixtures.
 *
 * Menu Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => MenuFixtures::class, 'key' => 'menu_fixtures'],
])]
class MenuFixtures
{
    private string $locale = '';
    private array $pages = [];
    private array $pagesParams = [];
    private ?User $user;
    private int $position = 1;

    /**
     * MenuFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add Menus.
     */
    public function add(Website $website, array $pages, array $pagesParams, ?User $user = null, ?Website $websiteToDuplicate = null): void
    {
        $this->locale = $website->getConfiguration()->getLocale();
        $this->pages = $pages;
        $this->pagesParams = $pagesParams;
        $this->user = $user;

        if ($websiteToDuplicate instanceof Website) {
            $this->addDbMenus($websiteToDuplicate, $website);
        } else {
            $this->addMenu($website, 'Principal', 'main');
            $this->addMenu($website, 'Pied de page', 'footer');
        }
    }

    /**
     * Add Refer DB Menus.
     */
    private function addDbMenus(Website $websiteToDuplicate, Website $website): void
    {
        $menus = $this->entityManager->getRepository(MenuEntities\Menu::class)->findBy(['website' => $websiteToDuplicate]);
        foreach ($menus as $referMenu) {
            $menu = new MenuEntities\Menu();
            $menu->setAdminName($referMenu->getAdminName());
            $menu->setSlug($referMenu->getSlug());
            $menu->setTemplate($referMenu->getTemplate());
            $menu->setMain($referMenu->isMain());
            $menu->setFooter($referMenu->isFooter());
            $menu->setWebsite($website);
            $menu->setPosition($referMenu->getPosition());
            $menu->setCreatedBy($this->user);
            $menu->setFixedOnScroll($referMenu->isFixedOnScroll());
            $this->entityManager->persist($menu);
            $this->entityManager->flush();
            $this->addDbLinks($referMenu, $menu);
        }
    }

    /**
     * Add Refer DB Links.
     */
    private function addDbLinks(MenuEntities\Menu $referMenu, MenuEntities\Menu $menu): void
    {
        $referLinks = $this->entityManager->getRepository(MenuEntities\Link::class)->findBy(['menu' => $referMenu], ['level' => 'ASC']);

        foreach ($referLinks as $referLink) {

            $link = new MenuEntities\Link();
            $link->setAdminName($referLink->getAdminName());
            $link->setMenu($menu);
            $link->setSlug($referLink->getSlug());
            $link->setLevel($referLink->getLevel());
            $link->setLocale($this->locale);
            $link->setPosition($referLink->getPosition());

            $parentLink = $referLink->getParent();
            if ($parentLink instanceof MenuEntities\Link) {
                $newParentLink = $this->entityManager->getRepository(MenuEntities\Link::class)->findOneBy(['menu' => $menu->getId(), 'slug' => $parentLink->getSlug(), 'level' => $parentLink->getLevel()]);
                $link->setParent($newParentLink);
            }

            $referIntl = $referLink->getIntl();
            $referPage = $referIntl->getTargetPage();
            $page = $referPage instanceof Page ? $this->entityManager->getRepository(Page::class)->findOneBy(['slug' => $referPage->getSlug(), 'website' => $menu->getWebsite()]) : null;

            $intl = new MenuEntities\LinkIntl();
            $intl->setLocale($this->locale);
            $intl->setTargetPage($page);
            $intl->setTargetLink($referIntl->getTargetLink());
            $intl->setTitle($referIntl->getTitle());
            $intl->setLink($link);
            $intl->setCreatedBy($this->user);
            $intl->setWebsite($menu->getWebsite());

            $link->setIntl($intl);
            $link->setCreatedBy($this->user);

            $this->entityManager->persist($link);
            $this->entityManager->persist($intl);
            $this->entityManager->flush();
        }
    }

    /**
     * Add Menu.
     */
    private function addMenu(Website $website, string $adminName, string $slug): void
    {
        $isMain = 'main' === $slug;
        $isFooter = 'footer' === $slug;
        $template = str_contains($slug, 'footer') ? 'footer' : $slug;

        $menu = new MenuEntities\Menu();
        $menu->setAdminName($adminName);
        $menu->setSlug($slug);
        $menu->setTemplate($template);
        $menu->setMain($isMain);
        $menu->setFooter($isFooter);
        $menu->setWebsite($website);
        $menu->setFixedOnScroll($isMain);
        $menu->setPosition($this->position);
        $menu->setCreatedBy($this->user);
        if ($menu->isFooter()) {
            $menu->setAlignment('center');
        }

        $this->entityManager->persist($menu);
        $this->addLinks($menu);
        ++$this->position;
    }

    /**
     * Add Link to menu.
     */
    private function addLinks(MenuEntities\Menu $menu): void
    {
        $position = 1;

        foreach ($this->pagesParams as $key => $params) {
            $params = (object) $params;
            $pageMenus = $params->menus;

            /** @var Page $page */
            $page = $this->pages[$params->reference] ?? null;

            if (in_array($menu->getSlug(), $pageMenus) && $page) {
                $link = new MenuEntities\Link();
                $link->setAdminName($page->getAdminName());
                $link->setMenu($menu);
                $link->setLocale($this->locale);
                $link->setPosition($position);

                $intl = new MenuEntities\LinkIntl();
                $intl->setTargetPage($page);
                $intl->setTitle($page->getAdminName());
                $intl->setLocale($this->locale);
                $intl->setLink($link);
                $intl->setCreatedBy($this->user);
                $intl->setWebsite($menu->getWebsite());

                $link->setIntl($intl);
                $link->setCreatedBy($this->user);

                $this->entityManager->persist($link);
                $this->entityManager->persist($intl);

                ++$position;
            }
        }
    }
}
