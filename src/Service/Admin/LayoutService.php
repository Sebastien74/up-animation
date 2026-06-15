<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Layout\Block;
use App\Entity\Layout\Layout;
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

    private const array SCREENS = ['', 'mobile', 'tablet', 'laptop'];
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

    public function standardizeLayoutMargins(Layout $layout): JsonResponse
    {
        foreach ($layout->getZones() as $zone) {
            $this->standardizeMarginsEL($zone);
            foreach ($zone->getCols() as $col) {
                $this->standardizeMarginsEL($col);
                foreach ($col->getBlocks() as $block) {
                    $this->standardizeMarginsEL($block);
                }
            }
        }

        return new JsonResponse(['success' => true]);
    }

    public function restoreLayoutMargins(Layout $layout): JsonResponse
    {
        $restored = false;
        foreach ($layout->getZones() as $zone) {
            $restored = $this->applyMarginsBackup($zone) || $restored;
            foreach ($zone->getCols() as $col) {
                $restored = $this->applyMarginsBackup($col) || $restored;
                foreach ($col->getBlocks() as $block) {
                    $restored = $this->applyMarginsBackup($block) || $restored;
                }
            }
        }

        if ($restored) {
            $this->coreLocator->em()->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    public function restoreMarginsEL(mixed $entity): JsonResponse
    {
        if (!method_exists($entity, 'getMarginsBackup')) {
            return new JsonResponse(['success' => false], 400);
        }
        if (empty($entity->getMarginsBackup())) {
            return new JsonResponse(['success' => false], 404);
        }

        $this->applyMarginsBackup($entity);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    private function applyMarginsBackup(mixed $entity): bool
    {
        if (!method_exists($entity, 'getMarginsBackup') || empty($backup = $entity->getMarginsBackup())) {
            return false;
        }

        foreach ($backup as $field => $value) {
            $setter = 'set'.ucfirst((string) $field);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        $entity->setMarginsBackup(null);

        $this->coreLocator->em()->persist($entity);

        return true;
    }

    public function standardizeMarginsEL(mixed $entity): JsonResponse
    {
        if (method_exists($entity, 'setMarginsBackup') && empty($entity->getMarginsBackup())) {
            $entity->setMarginsBackup($this->captureMargins($entity));
        }

        foreach (self::SIDES as $side) {
            foreach (['margin', 'padding'] as $type) {
                $getter = 'get'.ucfirst($type).ucfirst($side);
                if (!method_exists($entity, $getter)) {
                    continue;
                }
                $desktopValue = $entity->$getter();
                foreach (self::SCREENS as $screen) {
                    if ('' === $screen) {
                        continue;
                    }
                    $setter = 'set'.ucfirst($type).ucfirst($side).ucfirst($screen);
                    if (method_exists($entity, $setter)) {
                        $entity->$setter($desktopValue);
                    }
                }
            }
        }

        $this->coreLocator->em()->persist($entity);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    private function captureMargins(mixed $entity): array
    {
        $backup = [];
        foreach (self::SCREENS as $screen) {
            foreach (self::SIDES as $side) {
                foreach (['margin', 'padding'] as $type) {
                    $field = $type.ucfirst($side).ucfirst($screen);
                    $getter = 'get'.ucfirst($field);
                    if (method_exists($entity, $getter)) {
                        $backup[$field] = $entity->$getter();
                    }
                }
            }
        }

        return $backup;
    }
}