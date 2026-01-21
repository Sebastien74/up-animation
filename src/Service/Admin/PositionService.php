<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Service\Core\InterfaceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * PositionService.
 *
 * Manage entity position
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => PositionService::class, 'key' => 'position_service'],
])]
class PositionService
{
    private ?array $interface;
    private ?Request $request;
    private ?object $repository;
    private ?object $entity;
    private iterable $entities;
    private int $count;

    /**
     * PositionService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InterfaceHelper $interfaceHelper,
        private readonly IndexHelper $indexHelper,
    ) {
    }

    /**
     * Set Services vars.
     */
    public function setVars(string $classname, Request $request): void
    {
        $this->setInterface($classname);
        $this->setRequest($request);
        $this->setRepository($classname);
        $this->setEntity();
        $this->setEntities($classname);
    }

    /**
     * Set entity position.
     */
    public function execute(FormInterface $form, $postEntity): void
    {
        $oldPosition = $form->getConfig()->getOption('old_position');
        $newPosition = $form->getData()->getPosition();
        $start = $newPosition < $oldPosition ? $newPosition : $oldPosition + 1;
        $end = $newPosition < $oldPosition ? $oldPosition - 1 : $newPosition;
        $type = $newPosition < $oldPosition ? 'up' : 'down';

        foreach ($this->entities as $entity) {
            if ($entity !== $postEntity) {
                $position = $entity->getPosition();
                if ($position >= $start && $position <= $end) {
                    $newPosition = 'up' === $type ? $position + 1 : $position - 1;
                    $entity->setPosition($newPosition);
                }
                $this->entityManager->persist($entity);
            }
        }

        $this->entityManager->persist($postEntity);
        $this->entityManager->flush();
    }

    /**
     * Get Interface.
     */
    public function getInterface(): ?array
    {
        return $this->interface;
    }

    /**
     * Set Interface.
     */
    public function setInterface(string $classname): void
    {
        $this->interface = $this->interfaceHelper->generate($classname);
    }

    /**
     * Set Request.
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Set Entity.
     */
    public function setRepository(string $classname): void
    {
        $this->repository = $this->entityManager->getRepository($classname);
    }

    /**
     * Get Entity.
     */
    public function getEntity(): ?object
    {
        return $this->entity;
    }

    /**
     * Set Entity.
     */
    public function setEntity(): void
    {
        $this->entity = $this->repository->find($this->request->get($this->interface['name']));
    }

    /**
     * Get Entity[].
     */
    public function getEntities(): iterable
    {
        return $this->entities;
    }

    /**
     * Set Entities.
     */
    public function setEntities(string $classname): void
    {
        $this->indexHelper->setDisplaySearchForm(false);
        $this->indexHelper->execute($classname, $this->interface, 'all');
        $pagination = $this->indexHelper->getPagination();

        if ($pagination instanceof SlidingPagination) {
            $this->entities = $pagination->getItems();
            $this->count = $pagination->getTotalItemCount();
        } elseif (is_array($pagination)) {
            $this->entities = $pagination;
            $this->count = count($this->entities);
        } else {
            $this->entities = [];
        }
    }

    /**
     * Get count.
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Set entity values positions.
     */
    public function setByJsonArray(string $data, string $classname): void
    {
        $entity = null;
        $data = json_decode($data, true);

        if (count($data) > 0) {
            $interface = $this->interfaceHelper->generate($classname);
            $masterField = is_array($interface) && !empty($interface['masterField']) ? $interface['masterField'] : null;
            $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : null;

            foreach ($data as $item) {
                if ($item['id'] && $item['position']) {
                    $value = $this->entityManager->getRepository($classname)->find($item['id']);
                    if (is_object($value) && method_exists($value, 'setPosition')) {
                        $value->setPosition($item['position']);
                        if (method_exists($value, 'setFeaturePosition')) {
                            $value->setFeaturePosition($item['position']);
                        }
                        if ($masterFieldGetter && method_exists($value, $masterFieldGetter)) {
                            $entity = $value->$masterFieldGetter();
                        }
                        $this->entityManager->persist($value);
                    }
                }
            }

            $this->entityManager->flush();
        }

        if (is_object($entity)) {
            $this->setEntityValuesPositions($entity, $classname);
        }
    }

    /**
     * Set entity values positions.
     */
    public function setEntityValuesPositions(mixed $entity, string $classname): void
    {
        $associationMappings = $this->entityManager->getClassMetadata(get_class($entity))->getAssociationMappings();
        $associationGetter = null;
        foreach ($associationMappings as $mapping) {
            if (!empty($mapping['targetEntity']) && $mapping['targetEntity'] === $classname) {
                $associationGetter = !empty($mapping['fieldName']) ? 'get'.ucfirst($mapping['fieldName']) : null;
                break;
            }
        }

        if ($associationGetter && method_exists($entity, $associationGetter)) {
            $ids = [];
            $positions = [];
            $values = $entity->$associationGetter();
            $lastPosition = 0;

            foreach ($values as $value) {
                if (method_exists($value, 'getPosition') && !in_array($value->getPosition(), $positions)) {
                    $positions[] = $value->getPosition();
                    $ids[] = $value->getId();
                }
                if ($value->getPosition() > $lastPosition) {
                    $lastPosition = $value->getPosition();
                }
            }

            if (count($ids) !== $values->count()) {
                $position = $lastPosition + 1;
                foreach ($values as $value) {
                    if (method_exists($value, 'setPosition') && !in_array($value->getId(), $ids)) {
                        $value->setPosition($position);
                        $this->entityManager->persist($value);
                        ++$position;
                    }
                }
                $this->entityManager->flush();
                $this->entityManager->refresh($entity);
            }
        }
    }
}
