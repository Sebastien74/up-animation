<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Module\Menu\Menu;
use App\Form\Manager\Module\AddLinkManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * LayoutManager.
 *
 * Manage admin Layout form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LayoutManager::class, 'key' => 'layout_form_manager'],
])]
class LayoutManager
{
    private ?Request $request;

    /**
     * LayoutManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly AddLinkManager $addLinkManager,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Post Layout.
     *
     * @throws NonUniqueResultException
     */
    public function post(array $interface, FormInterface $form, Website $website): void
    {
        $entity = $form->getData();
        $this->setLayout($interface, $entity, $website);
        $this->setLayoutUpdatedAt($entity);
    }

    /**
     * To create Layout if not exist.
     *
     * @throws NonUniqueResultException
     */
    public function setLayout(array $interface, mixed $entity, Website $website): void
    {
        $haveInterface = !empty($interface['name']);
        $asFlush = $entity->getId();
        $asLayout = method_exists($entity, 'getLayout');
        $asCustomLayout = $asLayout && method_exists($entity, 'isCustomLayout') && $entity->isCustomLayout();
        $haveLayout = $asLayout && $entity->getLayout();

        /* To add Layout */
        if (!$asFlush && $asLayout && !$asCustomLayout && !$haveLayout && $haveInterface
            || $asFlush && $asLayout && $asCustomLayout && !$haveLayout && $haveInterface) {
            $layout = new Layout\Layout();
            $layout->setWebsite($interface['website']);
            $layout->setAdminName($entity->getAdminName());
            $entity->setLayout($layout);
            if ($entity instanceof Layout\Page) {
                $this->addZone($layout, $website, $entity);
            }
            $this->setGridZone($entity->getLayout());
            $this->entityManager->persist($layout);
            $this->entityManager->flush();
            $inMenu = isset($this->request->get('page')['inMenu']);
            if ($entity instanceof Layout\Page && $inMenu) {
                $this->addToMenu($entity, $website);
            }
        }
    }

    /**
     * Set Layout updatedAt and parent entity updatedAt.
     *
     * @throws Exception
     */
    private function setLayoutUpdatedAt(mixed $entity = null): void
    {
        $entityLayout = null;
        if ($entity instanceof Layout\Zone) {
            $entityLayout = $entity->getLayout();
        } elseif ($entity instanceof Layout\Col) {
            $entityLayout = $entity->getZone()->getLayout();
        } elseif ($entity instanceof Layout\Block) {
            $entityLayout = $entity->getCol()->getZone()->getLayout();
        }
        if ($entityLayout instanceof Layout\Layout) {
            $entityLayout->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $entityLayout->setParent($this->entityManager);
            $this->entityManager->persist($entityLayout);
            $parent = $entityLayout->getParent($this->entityManager);
            if ($parent) {
                $parent->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->entityManager->persist($parent);
            }
        }
    }

    /**
     * Set gird zone for front class mapping.
     */
    public function setGridZone(?Layout\Layout $layout = null): void
    {
        if (!$layout) {
            return;
        }

        $flush = false;

        foreach ($layout->getZones() as $zone) {
            $count = 0;
            $rows = [];
            $rowCount = 0;

            foreach ($zone->getCols() as $col) {
                $count = $count + $col->getSize();
                if ($count > 12) {
                    $count = intval($col->getSize());
                    ++$rowCount;
                }
                $rows[$rowCount][$col->getId()] = $col->getSize();
            }

            $grids = [];
            foreach ($rows as $cols) {
                $class = '';
                $colsArray = [];
                foreach ($cols as $colId => $size) {
                    $class .= $size.'-';
                    $colsArray[] = $colId;
                }

                $grids[] = (object) [
                    'grid' => rtrim($class, '-'),
                    'cols' => $colsArray,
                ];
            }

            $colsGrids = [];
            foreach ($grids as $grid) {
                foreach ($grid->cols as $col) {
                    $colsGrids[$col] = $grid->grid;
                }
            }

            if ($zone->getGrid() !== $colsGrids) {
                $flush = true;
                $zone->setGrid($colsGrids);
                $this->entityManager->persist($zone);
            }
        }

        if ($flush) {
            $this->entityManager->persist($layout);
            $this->entityManager->flush();
        }
    }

    /**
     * Add Zone Layout.
     */
    private function addZone(Layout\Layout $layout, Website $website, mixed $entity): void
    {
        $zone = new Layout\Zone();
        $zone->setFullSize(true);
        $zone->setPaddingTop('pt-0');
        $zone->setPaddingBottom('pb-0');
        $layout->addZone($zone);
        $this->addCol($zone, $website, $entity);
    }

    /**
     * Add Col.
     */
    private function addCol(Layout\Zone $zone, Website $website, mixed $entity): void
    {
        $col = new Layout\Col();
        $col->setPaddingRight('pe-0');
        $col->setPaddingLeft('ps-0');
        $zone->addCol($col);
        $this->addBlock($col, $website, $entity);
    }

    /**
     * Add Block.
     */
    private function addBlock(Layout\Col $col, Website $website, mixed $entity): void
    {
        $block = new Layout\Block();
        $col->addBlock($block);

        $intl = new Layout\BlockIntl();
        $intl->setTitle($entity->getAdminName());
        $intl->setWebsite($website);
        $intl->setLocale($website->getConfiguration()->getLocale());
        $intl->setBlock($block);
        $intl->setTitleForce(1);

        $block->addIntl($intl);

        $blockType = $this->entityManager->getRepository(Layout\BlockType::class)->findOneBy(['slug' => 'title-header']);
        $block->setBlockType($blockType);
        $block->setPaddingRight('pe-0');
        $block->setPaddingLeft('ps-0');
    }

    /**
     * Add to main menu.
     *
     * @throws NonUniqueResultException
     */
    private function addToMenu(Layout\Page $page, Website $website): void
    {
        $mainMenu = $this->entityManager->getRepository(Menu::class)->findOneBy([
            'main' => true,
            'website' => $website,
        ]);
        if ($mainMenu) {
            $this->addLinkManager->post(['page'.$page->getId() => $page], $mainMenu, $website->getConfiguration()->getLocale(), true);
        }
    }
}
