<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Core\Website;
use App\Entity\Layout;
use Doctrine\ORM\EntityManagerInterface;

/**
 * LayoutGeneratorService.
 *
 * Layout generation management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutGeneratorService
{
    /**
     * LayoutGeneratorService constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add Layout.
     */
    public function addLayout(Website $website, array $options = []): Layout\Layout
    {
        $position = count($this->entityManager->getRepository(Layout\Layout::class)->findBy(['website' => $website])) + 1;
        $layout = new Layout\Layout();
        $layout->setPosition($position);
        $layout->setWebsite($website);

        foreach ($options as $property => $value) {
            $setter = 'set'.ucfirst($property);
            if (method_exists($layout, $setter)) {
                $layout->$setter($value);
            }
        }

        $this->entityManager->persist($layout);

        return $layout;
    }

    /**
     * Add Zone.
     */
    public function addZone(Layout\Layout $layout, array $options = []): Layout\Zone
    {
        $zone = new Layout\Zone();
        $layout->addZone($zone);
        $zone->setPosition($layout->getZones()->count());

        foreach ($options as $property => $value) {
            $setter = 'set'.ucfirst($property);
            if (method_exists($zone, $setter)) {
                $zone->$setter($value);
            }
        }

        $this->entityManager->persist($layout);

        return $zone;
    }

    /**
     * Add Col.
     */
    public function addCol(Layout\Zone $zone, array $options = []): Layout\Col
    {
        $col = new Layout\Col();
        $zone->addCol($col);

        foreach ($options as $property => $value) {
            $setter = 'set'.ucfirst($property);
            if (method_exists($col, $setter)) {
                $col->$setter($value);
            }
        }

        $this->entityManager->persist($zone);

        return $col;
    }

    /**
     * Add Block.
     */
    public function addBlock(Layout\Col $col, array $options = []): Layout\Block
    {
        $blockTypeRepository = $this->entityManager->getRepository(Layout\BlockType::class);

        $block = new Layout\Block();
        $col->addBlock($block);
        $block->setPosition($col->getBlocks()->count());

        foreach ($options as $property => $value) {
            $setter = 'set'.ucfirst($property);
            if (method_exists($block, $setter)) {
                if ('setBlockType' === $setter) {
                    $value = $blockTypeRepository->findOneBy(['slug' => $value]);
                }
                $block->$setter($value);
            }
        }

        $this->entityManager->persist($col);

        return $block;
    }

    /**
     * Flush.
     */
    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
