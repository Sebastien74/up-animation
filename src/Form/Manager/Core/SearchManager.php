<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Module\Catalog\FeatureValueProduct;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * SearchManager.
 *
 * Manage admin search in index
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SearchManager::class, 'key' => 'core_search_form_manager'],
])]
class SearchManager
{
    private ?Request $request;
    private array $interface = [];
    private ?string $masterField;
    private ?object $entity;
    private array $fields = [];
    private QueryBuilder $queryBuilder;
    private array $alias = [];

    /**
     * SearchManager constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
        $this->request = $this->requestStack->getCurrentRequest();
    }

    /**
     * Get form search QueryBuilder.
     */
    public function execute(FormInterface $form, array $interface): Query
    {
        $this->setInterface($interface);
        $this->setMasterField();
        $this->setEntity();
        $this->setFields();

        $searchValue = $form->getData()['search'];
        $classname = $this->interface['classname'];
        $referClass = new $classname();

        $this->queryBuilder = $this->entityManager->getRepository($this->interface['classname'])->createQueryBuilder('e');

        if (!empty($searchValue)) {
            foreach ($this->fields as $field) {
                $fieldName = !empty($field['meta']['fieldName']) ? $field['meta']['fieldName'] : null;
                $existing = method_exists($this->entity, 'get'.ucfirst($fieldName)) || method_exists($this->entity, 'is'.ucfirst($fieldName));
                if (!empty($fieldName) && is_object($this->entity) && $existing) {
                    $this->setQuery($field, $searchValue);
                }
            }
            if (!empty($this->masterField) && !empty($this->request->get($this->masterField))) {
                $this->queryBuilder->andWhere('e.'.$this->masterField.' = :'.$this->masterField);
                $this->queryBuilder->setParameter($this->masterField, $this->request->get($this->masterField));
            }
            if (is_object($referClass) && method_exists($referClass, 'getUrls')) {
                $this->queryBuilder->leftJoin('e.urls', 'u');
                $this->queryBuilder->andWhere('u.archived = :archived');
                $this->queryBuilder->setParameter('archived', false);
                $this->queryBuilder->addSelect('u');
            }
        }

        return $this->queryBuilder->getQuery();
    }

    /**
     * Set interface.
     */
    private function setInterface(array $interface): void
    {
        $this->interface = $interface;
    }

    /**
     * Set masterField.
     */
    private function setMasterField(): void
    {
        $this->masterField = !empty($this->interface['masterField']) ? $this->interface['masterField'] : null;
    }

    /**
     * Set entity.
     */
    private function setEntity(): void
    {
        $this->entity = $this->interface['entity'];
    }

    /**
     * Set fields.
     */
    private function setFields(): void
    {
        $metadata = $this->entityManager->getClassMetadata($this->interface['classname']);
        $fields = !empty($this->interface['configuration'])
        && property_exists($this->interface['configuration'], 'searchFields')
        && $this->interface['configuration']->searchFields
            ? $this->interface['configuration']->searchFields : ['adminName'];

        foreach ($fields as $field) {
            try {
                $matches = [];
                $fieldName = $field;
                if (preg_match('/./', $field)) {
                    $matches = explode('.', $field);
                    $fieldName = $matches[0];
                }
                if (!empty($metadata->getAssociationMappings()[$fieldName])) {
                    $this->fields[] = [
                        'meta' => $metadata->getAssociationMappings()[$fieldName],
                        'joinProperty' => !empty($matches[1]) ? $matches[1] : 'adminName',
                    ];
                } elseif (!empty($metadata->fieldMappings[$fieldName])) {
                    $this->fields[] = [
                        'meta' => $metadata->getFieldMapping($fieldName),
                    ];
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        $excludeTypes = ['datetime', 'boolean'];
        foreach ($this->fields as $key => $field) {
            if (in_array($field['meta']['type'], $excludeTypes)) {
                unset($this->fields[$key]);
            }
        }
    }

    /**
     * Set Query.
     */
    private function setQuery(array $field, string $searchValue): void
    {
        $fieldName = $field['meta']['fieldName'];
        $alias = substr(str_shuffle($fieldName).uniqid(), 0, 5);

        if ('string' === $field['meta']['type'] || 'text' === $field['meta']['type']) {
            $this->queryBuilder->orWhere('e.'.$fieldName.' LIKE :'.$fieldName);
            $this->queryBuilder->setParameter($fieldName, '%'.$searchValue.'%');
        } elseif (!empty($field['joinProperty']) && !in_array($alias, $this->alias)) {
            $targetEntity = new $field['meta']['targetEntity']();
            $joinProperty = $field['joinProperty'];
            $existing = method_exists($targetEntity, 'get'.ucfirst($joinProperty)) || method_exists($targetEntity, 'is'.ucfirst($joinProperty));
            if ($existing) {
                $this->queryBuilder->leftJoin('e.'.$fieldName, $alias);
                $this->queryBuilder->orWhere($alias.'.'.$joinProperty.' LIKE :'.$fieldName);
                $this->queryBuilder->setParameter($fieldName, '%'.$searchValue.'%');
                $this->queryBuilder->addSelect($alias);
                if ('values' === $fieldName && !empty($field['meta']['targetEntity']) && FeatureValueProduct::class === $field['meta']['targetEntity']) {
                    $aliasValues = substr(str_shuffle($fieldName).uniqid(), 0, 5);
                    $this->queryBuilder->leftJoin($alias.'.value', $aliasValues);
                    $this->queryBuilder->orWhere($aliasValues.'.adminName LIKE :'.$fieldName);
                    $this->queryBuilder->addSelect($aliasValues);
                }
                $this->alias[] = $this->alias;
            }
        }
    }
}
