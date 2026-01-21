<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Block;
use App\Entity\Layout\Zone;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * LayoutService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutService implements LayoutServiceInterface
{
    /**
     * LayoutService constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    private const array SCREENS = ['', 'mobile', 'tablet', 'miniPc'];
    private const array SIDES = ['top', 'right', 'bottom', 'left'];

    public function resetMargins(Zone $zone): JsonResponse
    {
        $this->resetMarginsEL($zone);
        foreach ($zone->getCols() as $col) {
            $this->resetMarginsEL($col);
            foreach ($col->getBlocks() as $block) {
                $this->resetMarginsEL($block);
            }
        }

        return new JsonResponse(['success' => true]);
    }

    public function resetMarginsEL(mixed $entity): JsonResponse
    {
        foreach (self::SCREENS as $screen) {
            foreach (self::SIDES as $side) {
                foreach (['margin', 'padding'] as $type) {
                    $setter = 'set'.ucfirst($type).ucfirst($side).ucfirst($screen);
                    if (method_exists($entity, $setter)) {
                        $margin = null;
                        if ('' === $screen && $entity instanceof Zone && 'padding' === $type && in_array($side, ['top', 'bottom'])) {
                            $margin = 'top' === $side ? 'pt-lg' : 'pb-lg';
                        }
                        if ('' === $screen && $entity instanceof Block && 'padding' === $type && in_array($side, ['right', 'left'])) {
                            $margin = 'left' === $side ? 'ps-0' : 'pe-0';
                        }
                        $entity->$setter($margin);
                    }
                }
            }
        }

        $this->coreLocator->em()->persist($entity);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }
}