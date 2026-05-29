<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Form\Manager\Core\SearchManager;
use App\Form\Type\Core\IndexSearchType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * IndexHelper.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => IndexHelper::class, 'key' => 'index_helper'],
])]
class IndexHelper
{
    private ?Request $request;
    private object $repository;
    private PaginationInterface $pagination;
    private ?object $entityConf = null;
    private bool $displaySearchForm = false;
    private ?int $archivedCount = null;
    private ?FormInterface $searchForm = null;

    /**
     * IndexHelper constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly PaginatorInterface $paginator,
        private readonly SearchManager $searchManager,
        private readonly FormFactoryInterface $formFactory,
    ) {
        $this->request = $this->coreLocator->request();
    }

    /**
     * Execute helper.
     */
    public function execute(string $classname, array $interface, mixed $limit = null, mixed $entities = null, bool $forceEntities = false, bool $onlyArchived = false): void
    {
        $this->setRepository($classname);
        if (!$onlyArchived) {
            $this->setEntityConf($interface);
            $this->setSearchForm($interface);
            $this->setPagination($interface, $limit, $entities, $forceEntities);
        }
        $this->setArchivedCount($interface);
    }

    /**
     * Get entity Repository.
     */
    public function getRepository(): object
    {
        return $this->repository;
    }

    /**
     * Set entity Repository.
     */
    public function setRepository(string $classname): void
    {
        $this->repository = $this->coreLocator->em()->getRepository($classname);
    }

    /**
     * Get entity configuration.
     */
    public function getEntityConf(): ?object
    {
        return $this->entityConf;
    }

    /**
     * Set entity configuration.
     */
    public function setEntityConf(array $interface): void
    {
        $this->entityConf = $interface['configuration'];
    }

    /**
     * Set is display searchForm.
     */
    public function setDisplaySearchForm(bool $display): void
    {
        $this->displaySearchForm = $display;
    }

    /**
     * Get search Form.
     */
    public function getSearchForm(): ?FormInterface
    {
        return $this->searchForm;
    }

    /**
     * Set a search Form.
     */
    public function setSearchForm(array $interface): void
    {
        if ($this->displaySearchForm) {
            $this->searchForm = $this->formFactory->createBuilder(IndexSearchType::class, null, ['interface' => $interface])
                ->setMethod('GET')
                ->getForm();
        }
    }

    /**
     * Get pagination.
     */
    public function getPagination(): PaginationInterface
    {
        return $this->pagination;
    }

    /**
     * Get pagination.
     */
    public function getArchivedCount(): ?int
    {
        return $this->archivedCount;
    }

    /**
     * Set pagination.
     */
    public function setPagination(array $interface, mixed $limit, mixed $entities = null, bool $forceEntities = false): void
    {
        $queryLimit = $this->entityConf->adminLimit !== $limit ? $this->entityConf->adminLimit : $limit;
        $queryLimit = 'all' === $limit ? 100000000 : $queryLimit;
        if ($this->displaySearchForm) {
            $this->searchForm->handleRequest($this->request);
        }
        $queryBuilder = $this->getQueryBuilder($interface, $entities, $forceEntities);
        $this->pagination = $this->paginator->paginate(
            $queryBuilder,
            $this->request->query->getInt('page', 1),
            $queryLimit,
            ['wrap-queries' => true]
        );
        $this->preloadRelations();
    }

    /**
     * Warm the lazy relations rendered by the listing template for the current page,
     * in bounded separate queries. Collections are warmed one query each to avoid a
     * multi-collection cartesian product on the paginated query.
     */
    private function preloadRelations(): void
    {
        $items = $this->pagination->getItems();
        $items = is_array($items) ? array_values($items) : iterator_to_array($items, false);
        $className = $this->repository->getClassName();
        if (empty($items) || !($items[0] instanceof $className)) {
            return;
        }

        $em = $this->coreLocator->em();
        $metadata = $em->getClassMetadata($className);

        if ($metadata->hasAssociation('mediaRelations')) {
            $queryBuilder = $this->repository->createQueryBuilder('e')
                ->leftJoin('e.mediaRelations', 'mr')->addSelect('mr')
                ->where('e IN (:entities)')->setParameter('entities', $items);
            $relation = $em->getClassMetadata($metadata->getAssociationMapping('mediaRelations')['targetEntity']);
            if ($relation->hasAssociation('media')) {
                $queryBuilder->leftJoin('mr.media', 'mrMedia')->addSelect('mrMedia');
            }
            if ($relation->hasAssociation('intl')) {
                $queryBuilder->leftJoin('mr.intl', 'mrIntl')->addSelect('mrIntl');
            }
            $queryBuilder->getQuery()->getResult();
        }

        if ($metadata->hasAssociation('urls')) {
            $this->repository->createQueryBuilder('e')
                ->leftJoin('e.urls', 'warmUrls')->addSelect('warmUrls')
                ->where('e IN (:entities)')->setParameter('entities', $items)
                ->getQuery()->getResult();
        }

        foreach ($this->columnAssociations($metadata) as $index => $association) {
            $alias = 'warmCol'.$index;
            $this->repository->createQueryBuilder('e')
                ->leftJoin('e.'.$association, $alias)->addSelect($alias)
                ->where('e IN (:entities)')->setParameter('entities', $items)
                ->getQuery()->getResult();
        }
    }

    /**
     * Distinct association heads referenced by dotted listing columns (e.g. "group.adminName").
     *
     * @return string[]
     */
    private function columnAssociations(ClassMetadata $metadata): array
    {
        if (!is_object($this->entityConf) || !method_exists($this->entityConf, 'getColumns')) {
            return [];
        }
        $associations = [];
        foreach ((array) $this->entityConf->getColumns() as $column) {
            if (!str_contains((string) $column, '.')) {
                continue;
            }
            $head = explode('.', (string) $column)[0];
            if (in_array($head, $associations, true) || in_array($head, ['mediaRelations', 'urls'], true)) {
                continue;
            }
            if ($metadata->hasAssociation($head)) {
                $associations[] = $head;
            }
        }

        return array_values($associations);
    }

    /**
     * Set archived entities.
     */
    public function setArchivedCount(array $interface): void
    {
        $entity = !empty($interface['entity']) ? $interface['entity'] : null;
        if ($entity && method_exists($entity, 'getUrls')) {
            $queryBuilder = $this->repository->createQueryBuilder('e')
                ->leftJoin('e.urls', 'u')
                ->andWhere('u.archived = :archived')
                ->setParameter('archived', true);
            if (method_exists($entity, 'getWebsite')) {
                $queryBuilder->andWhere('u.website = :website')
                    ->setParameter('website', $this->coreLocator->website()->entity);
            }
            $this->archivedCount = (int) $queryBuilder->select('COUNT(DISTINCT e.id)')
                ->getQuery()
                ->getSingleScalarResult();
        }
    }

    /**
     * Get QueryBuilder.
     */
    private function getQueryBuilder(array $interface, $entities = null, bool $forceEntities = false)
    {
        $queryBuilder = null;

        if ($this->displaySearchForm && $this->searchForm->isSubmitted() && !empty($this->searchForm->getData()['search'])) {
            $queryBuilder = $this->searchManager->execute($this->searchForm, $interface);
        }

        if (!$queryBuilder) {
            if ($entities || $forceEntities) {
                $queryBuilder = $entities;
            } elseif (!empty($interface['entity']) && method_exists($interface['entity'], 'getUrls')) {
                $params = $this->getQueryParams($interface);
                /** @var QueryBuilder $queryBuilder */
                $queryBuilder = $this->repository->createQueryBuilder('e')
                    ->leftJoin('e.urls', 'u')
                    ->andWhere('u.archived = :archived')
                    ->setParameter('archived', false)
                    ->orderBy('e.'.$this->entityConf->orderBy, $this->entityConf->orderSort);
                if ($this->coreLocator->em()->getClassMetadata($this->repository->getClassName())->hasAssociation('intls')) {
                    $queryBuilder->leftJoin('e.intls', 'i')->addSelect('i');
                }
                foreach ($params as $name => $param) {
                    $queryBuilder->andWhere('e.'.$name.' = :'.$name);
                    $queryBuilder->setParameter($name, $param);
                }
            } elseif (is_object($interface['entity']) && property_exists($interface['entity'], $this->entityConf->orderBy)
                && is_object($this->searchForm) && !$this->searchForm->isSubmitted()) {
                $queryBuilder = $this->repository->findBy($this->getQueryParams($interface), [$this->entityConf->orderBy => $this->entityConf->orderSort]);
            } else {
                $orderBy = is_object($this->entityConf) && method_exists($this->entityConf, 'orderBy') ? $this->entityConf->orderBy : 'position';
                $properties = explode('.', $orderBy);
                $params = $this->getQueryParams($interface);
                if (2 == count($properties)) {
                    /** @var QueryBuilder $queryBuilder */
                    $queryBuilder = $this->repository->createQueryBuilder('e');
                    foreach ($params as $property => $value) {
                        if ($value) {
                            $queryBuilder->andWhere('e.'.$property.' = :'.$property)
                                ->setParameter($property, $value);
                        }
                    }
                    $orderSort = is_object($this->entityConf) && method_exists($this->entityConf, 'orderSort') ? $this->entityConf->orderSort : 'ASC';
                    $queryBuilder->leftJoin('e.'.$properties[0], 'j')
                        ->orderBy('j.'.$properties[1], $orderSort);
                } else {
                    $orderBy = $this->entityConf && $interface['entity'] && method_exists($interface['entity'], 'get'.ucfirst($this->entityConf->orderBy))
                        ? [$this->entityConf->orderBy => $this->entityConf->orderSort] : [];
                    $queryBuilder = $this->repository->findBy($params, $orderBy);
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * Get Query Params.
     */
    private function getQueryParams(array $interface): array
    {
        $masterField = !empty($interface['masterField']) ? $interface['masterField'] : null;
        $parentMasterField = !empty($interface['parentMasterField']) ? $interface['parentMasterField'] : null;
        $masterFieldRequest = $masterField && $this->request->attributes->get($masterField)
            ? $this->request->attributes->get($masterField)
            : ($masterField ? $this->request->query->get($masterField) : null);
        $parentMasterFieldRequest = $parentMasterField && $this->request->attributes->get($parentMasterField)
            ? $this->request->attributes->get($parentMasterField)
            : ($parentMasterField ? $this->request->query->get($parentMasterField) : null);

        if ($masterField && $parentMasterField) {
            return [
                $interface['masterField'] => $masterFieldRequest,
                $interface['parentMasterField'] => $parentMasterFieldRequest,
            ];
        } elseif ($masterField && 'website' === $interface['masterField']) {
            return [
                'website' => $interface['website'],
            ];
        } elseif ($masterField && 'configuration' === $interface['masterField']) {
            return [
                'configuration' => $interface['website']->getConfiguration(),
            ];
        } elseif ($masterField && $masterFieldRequest) {
            return [
                $interface['masterField'] => $masterFieldRequest,
            ];
        }

        return [];
    }
}
