<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Module\Recruitment\Category;
use App\Entity\Module\Recruitment\Contract;
use App\Entity\Module\Recruitment\Job;
use App\Entity\Module\Recruitment\Listing;
use App\Model\Core\WebsiteModel;
use App\Repository\Module\Recruitment\CategoryRepository;
use App\Repository\Module\Recruitment\ContractRepository;
use App\Service\Content\ActionService;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * JobFiltersManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class JobFiltersManager implements JobFiltersInterface
{
    /**
     * JobFiltersManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CategoryRepository $categoryRepository,
        private readonly ContractRepository $contractRepository,
        private readonly ActionService $actionService,
    ) {
    }

    /**
     * Get form filters values.
     */
    public function getFilters(): array
    {
        $unset = ['page', 'ajax', 'website'];
        $filters = [];
        $getRequest = filter_input_array(INPUT_GET);
        if ($getRequest) {
            foreach ($getRequest as $name => $value) {
                if (!in_array($name, $unset)) {
                    if ('domain' === $name && $value) {
                        $filters[$name] = $this->categoryRepository->find(intval($value));
                    } elseif ('contract' === $name && $value) {
                        $filters[$name] = $this->contractRepository->find(intval($value));
                    } elseif ($value) {
                        $filters[$name] = $value;
                    }
                }
            }
        }

        return $filters;
    }

    /**
     * Get results.
     *
     * @throws \Exception|InvalidArgumentException
     */
    public function getResults(Listing $entity, array $filters = [], array $entities = []): array
    {
        $website = WebsiteModel::fromEntity($entity->getWebsite(), $this->coreLocator);
        $this->actionService->setWebsite($website);
        $this->actionService->setClassname(Job::class);

        $filterCategoryId = !empty($filters['domain']) && $filters['domain'] instanceof Category ? $filters['domain']->getId() : null;
        $filterContractId = !empty($filters['contract']) && $filters['contract'] instanceof Contract ? $filters['contract']->getId() : null;
        $filterZipcode = !empty($filters['zipcode']) ? $filters['zipcode'] : false;

        foreach ($entities as $key => $entity) {
            /** @var Job $entity */
            if (($filterCategoryId && !$entity->getCategory()) || ($filterCategoryId && $filterCategoryId !== $entity->getCategory()->getId())) {
                unset($entities[$key]);
            }
            if (($filterContractId && !$entity->getContract()) || ($filterContractId && $filterContractId !== $entity->getContract()->getId())) {
                unset($entities[$key]);
            }
            if (($filterZipcode && !$entity->getZipCode()) || ($filterZipcode && $filterZipcode !== $entity->getZipCode())) {
                unset($entities[$key]);
            }
        }

        return $entities;
    }
}
