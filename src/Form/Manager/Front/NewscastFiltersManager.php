<?php

declare(strict_types=1);

namespace App\Form\Manager\Front;

use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Listing;
use App\Entity\Module\Newscast\Newscast;
use App\Entity\Module\Newscast\Teaser;
use App\Model\Core\WebsiteModel;
use App\Repository\Module\Newscast\CategoryRepository;
use App\Service\Content\ActionService;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * NewscastFiltersManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastFiltersManager implements NewscastFiltersInterface
{
    /**
     * NewscastFiltersManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CategoryRepository $categoryRepository,
        private readonly ActionService $actionService,
    ) {
    }

    /**
     * Get form filters values.
     */
    public function getFilters(): array
    {
        $excludedPatterns = ['utm_', 'ajax', 'fbclid', 'text', 'page', 'website'];
        $filters = [];
        $getRequest = filter_input_array(INPUT_GET);
        if ($getRequest) {
            foreach ($getRequest as $name => $value) {
                if (!in_array($name, $excludedPatterns)) {
                    if ('category' === $name && $value) {
                        $filters[$name] = $this->categoryRepository->find(intval($value));
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
    public function getResults(Listing|Teaser $entity, array $filters = [], array $entities = []): array
    {
        $website = WebsiteModel::fromEntity($entity->getWebsite(), $this->coreLocator);
        $this->actionService->setWebsite($website);
        $this->actionService->setClassname(Newscast::class);

        $filterCategoryId = !empty($filters['category']) && $filters['category'] instanceof Category ? $filters['category']->getId() : null;
        foreach ($entities as $key => $entity) {
            /** @var Newscast $entity */
            $eventCategoryId = $entity->getCategory() instanceof Category ? $entity->getCategory()->getId() : null;
            if ($filterCategoryId && $eventCategoryId !== $filterCategoryId) {
                unset($entities[$key]);
            }
        }

        return $entities;
    }
}
