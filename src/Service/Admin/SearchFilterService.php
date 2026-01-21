<?php

declare(strict_types=1);

namespace App\Service\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * SearchFilterService.
 *
 * Manage filter search
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SearchFilterService::class, 'key' => 'search_filter_service'],
])]
class SearchFilterService
{
    /**
     * SearchFilterService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilterBuilderUpdaterInterface $builderUpdater,
    ) {
    }

    /**
     * Execute search filter process.
     */
    public function execute(Request $request, FormInterface $form, array $interface): array
    {
        $repository = $this->entityManager->getRepository($interface['classname']);
        $filterBuilder = $repository->createQueryBuilder('e');
        if (!empty($interface['masterField']) && $request->get($interface['masterField'])) {
            $filterBuilder->andWhere('e.'.$interface['masterField'].' = :'.$interface['masterField']);
            $filterBuilder->setParameter($interface['masterField'], $request->get($interface['masterField']));
        }
        $this->builderUpdater->addFilterConditions($form, $filterBuilder);

        return $filterBuilder->getQuery()->getResult();
    }
}
