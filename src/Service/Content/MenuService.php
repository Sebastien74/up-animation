<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Module\Menu;
use App\Entity\Module\Menu\Link;
use App\Entity\Seo\Url;
use App\Model\Core\WebsiteModel;
use App\Model\Module\MenuModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * MenuService.
 *
 * To get menus.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class MenuService implements MenuServiceInterface
{
    /**
     * MenuService constructor.
     */
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get all menus.
     *
     * @throws NonUniqueResultException|MappingException
     */
    public function all(WebsiteModel $website, ?Url $url = null): array
    {
        $response = [];

        $menus = $this->coreLocator->emQuery()->findBy(Menu\Menu::class, 'website_id', $website->id);
        $links = $this->links($website->entity);
        foreach ($menus as $menu) {
            $code = $menu->isMain() ? 'main' : ($menu->isFooter() ? 'footer' : $menu->getSlug());
            $code = !empty($response[$code]) ? $menu->getSlug() : $code;
            $code = !empty($response[$code]) ? $code.'-'.$menu->getId() : $code;
            $menuLinks = !empty($links[$menu->getId()]) ? $links[$menu->getId()] : [];
            $response[$code] = MenuModel::fromEntity($menu, $website, $this->coreLocator, $menuLinks, $url);
        }

        return $response;
    }

    /**
     * To get all website links.
     */
    private function links(Website $website): array
    {
        $links = $this->coreLocator->em()->getRepository(Link::class)
            ->createQueryBuilder('l')
            ->innerJoin('l.menu', 'm')
            ->leftJoin('l.intl', 'i')
            ->leftJoin('i.targetPage', 'tp')
            ->leftJoin('tp.urls', 'tpu')
            ->leftJoin('l.mediaRelation', 'mr')
            ->leftJoin('mr.intl', 'mi')
            ->leftJoin('mr.media', 'me')
            ->leftJoin('l.parent', 'p')
            ->andWhere('m.website =  :website')
            ->andWhere('i.locale =  :locale')
            ->setParameter('website', $website)
            ->setParameter('locale', $this->coreLocator->request()->getLocale())
            ->orderBy('m.position', 'ASC')
            ->addOrderBy('l.position', 'ASC')
            ->addOrderBy('l.level', 'ASC')
            ->addSelect('m')
            ->addSelect('i')
            ->addSelect('tp')
            ->addSelect('tpu')
            ->addSelect('mr')
            ->addSelect('mi')
            ->addSelect('me')
            ->addSelect('p')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($links as $link) {
            $result[$link->getMenu()->getId()][] = $link;
        }

        return $result;
    }
}
