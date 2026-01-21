<?php

declare(strict_types=1);

namespace App\Service\Content;

use App\Entity\Layout\Page;
use App\Model\Core\WebsiteModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ActionService.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ActionService::class, 'key' => 'action_service'],
])]
class ActionService
{
    private ?Request $request;
    private string $locale = '';
    private WebsiteModel $website;
    private string $classname = '';
    private string $categoryClassname = '';
    private mixed $listing = null;

    /**
     * ActionService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
    ) {
        $this->setRequest();
    }

    /**
     * Set Request.
     */
    public function setRequest(): void
    {
        $this->request = $this->requestStack->getMainRequest();
        if ($this->request) {
            $this->locale = $this->request->getLocale();
        }
    }

    /**
     * Set WebsiteModel.
     */
    public function setWebsite(WebsiteModel $website): void
    {
        $this->website = $website;
    }

    /**
     * Set classname.
     */
    public function setClassname(string $classname): void
    {
        $this->classname = $classname;
    }

    /**
     * Set category classname.
     */
    public function setCategoryClassname(string $classname): void
    {
        $this->categoryClassname = $classname;
    }

    /**
     * To get entity Category.
     */
    public function getCategory($entity): mixed
    {
        $category = null;
        if (method_exists($entity, 'getCategory')) {
            /* Add default category if is NULL */
            if (!$entity->getCategory() && $this->categoryClassname) {
                $referCategory = new $this->categoryClassname();
                if (method_exists($referCategory, 'getIsAsDefault')) {
                    $category = $this->entityManager->getRepository($this->categoryClassname)->findOneBy([
                        'website' => $this->website->entity,
                        'asDefault' => true,
                    ]);
                    $entity->setCategory($category);
                }
            }
            $category = $entity->getCategory();
        }

        return $category;
    }

    /**
     * To get last entity.
     *
     * @throws NonUniqueResultException
     */
    public function getLastEntity($listing)
    {
        $this->listing = $listing;
        $referEntity = new $this->classname();
        $orders = explode('-', $listing->getOrderBy());
        $sort = $orders[0];
        $orderBy = strtoupper($orders[1]);
        $qb = $this->optimizedQueryBuilder($orderBy[0], strtoupper($orderBy[1]))
            ->setMaxResults(1);
        if (method_exists($listing, 'getCategories')) {
            $categoriesIds = [];
            foreach ($listing->getCategories() as $key => $category) {
                $categoriesIds[] = $category->getId();
            }
            if (!empty($categoriesIds)) {
                $qb->andWhere('c.id IN (:categoriesIds)')
                    ->setParameter('categoriesIds', $categoriesIds);
            }
        }
        if ($this->request->get('category') && method_exists($referEntity, 'getCategory')) {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $this->request->get('category'));
        }
        if ($this->listing && property_exists($this->listing, 'pastEvents') && $this->listing->isPastEvents() && 'startDate' === $sort
            && method_exists($referEntity, 'getStartDate')) {
            $qb->andWhere('e.startDate IS NOT NULL AND e.startDate >= CURRENT_TIMESTAMP()');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get entities by Listing.
     */
    public function findByListing(mixed $listing, mixed $excludeEntity = null, bool $disabledRelation = true): array
    {
        $this->listing = $listing;
        $referEntity = new $this->classname();
        $orderBy = method_exists($listing, 'getOrderBy') ? explode('-', $listing->getOrderBy()) : [];
        $sort = !empty($orderBy[0]) ? $orderBy[0] : null;
        $order = !empty($orderBy[1]) ? $orderBy[1] : (method_exists($listing, 'getOrderSort') ? $listing->getOrderSort() : null);

        $qb = $this->optimizedQueryBuilder($sort, strtoupper($order));

        if (method_exists($listing, 'getCategories')) {
            $categoryIds = [];
            foreach ($listing->getCategories() as $category) {
                $categoryIds[] = $category->getId();
            }
            if ($categoryIds) {
                $qb->andWhere('e.category IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds);
            }
        }

        if (!$disabledRelation && method_exists($referEntity, 'getIntls')) {
            $qb->leftJoin('e.intls', 'intl')
                ->andWhere('intl.locale = :locale')
                ->addSelect('intl');
        }

        if (!$disabledRelation && method_exists($referEntity, 'getMediaRelations')) {
            $qb->leftJoin('e.mediaRelations', 'mediaRelations')
                ->addSelect('mediaRelations');
        }

        if (method_exists($listing, 'isPromote') && $listing->isPromote()) {
            $qb->andWhere('e.promote = :promote')
                ->setParameter('promote', true);
        }

        if ($excludeEntity) {
            $qb->andWhere('e.id != :excludeId')
                ->setParameter('excludeId', $excludeEntity->getId());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find Page by URL code and locale.
     */
    public function findPageByUrlCodeAndLocale(string $pageUrl, bool $preview = false): ?Page
    {
        return $this->entityManager->getRepository(Page::class)->findByUrlCodeAndLocale($this->website, $pageUrl, $this->locale, $preview);
    }

    /**
     * Find entity by url & locale.
     *
     * @throws NonUniqueResultException
     */
    public function findEntityByUrlAndLocale(string $url, bool $preview = false): mixed
    {
        $referEntity = new $this->classname();
        if (method_exists($referEntity, 'getUrls')) {
            $qb = $this->optimizedQueryBuilder(null, null, $preview)
                ->andWhere('u.code = :code')
                ->setParameter('code', $url);
            if (method_exists($referEntity, 'getIntls')) {
                $qb->leftJoin('e.intls', 'intl')
                    ->andWhere('intl.locale = :locale')
                    ->addSelect('intl');
            }

            return $qb->getQuery()->getOneOrNullResult();
        }

        return null;
    }

    /**
     * optimizedQueryBuilder.
     */
    private function optimizedQueryBuilder(?string $sort = null, ?string $order = null, bool $preview = false): QueryBuilder
    {
        $referEntity = new $this->classname();
        $sort = $sort ?: 'publicationStart';
        $order = $order ?: 'DESC';

        $statement = $this->entityManager->getRepository($this->classname)
            ->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')
            ->andWhere('e.website = :website')
            ->setParameter('website', $this->website->entity)
            ->addSelect('w');

        if (method_exists($referEntity, 'getCategory')) {
            $statement->leftJoin('e.category', 'c')
                ->addSelect('c');
        }

        if (method_exists($referEntity, 'getUrls')) {
            $statement->leftJoin('e.urls', 'u')
                ->leftJoin('u.seo', 's')
                ->andWhere('u.locale = :locale')
                ->setParameter('locale', $this->locale)
                ->addSelect('u')
                ->addSelect('s');
        }

        if (!$preview) {
            if (method_exists($referEntity, 'getPublicationStart') && method_exists($referEntity, 'getPublicationEnd')) {
                $statement->andWhere('e.publicationStart IS NULL OR e.publicationStart < CURRENT_TIMESTAMP()')
                    ->andWhere('e.publicationEnd IS NULL OR e.publicationEnd > CURRENT_TIMESTAMP()')
                    ->andWhere('e.publicationStart IS NOT NULL');
            }
            $displayPastEvents = $this->listing && property_exists($this->listing, 'pastEvents') && $this->listing->isPastEvents();
            if ($displayPastEvents && 'startDate' === $sort
                && method_exists($referEntity, 'getStartDate') && !method_exists($referEntity, 'getEndDate')) {
                $statement->andWhere('e.startDate IS NOT NULL');
            } elseif ($this->listing && method_exists($this->listing, 'isAsEvents') && $this->listing->isAsEvents() && 'startDate' === $sort
                && method_exists($referEntity, 'getStartDate') && method_exists($referEntity, 'getEndDate')) {
                if ($displayPastEvents) {
                    $statement->andWhere('e.startDate IS NOT NULL');
                } else {
                    $statement->andWhere('e.startDate IS NOT NULL AND e.startDate >= CURRENT_TIMESTAMP()')
                        ->andWhere('e.endDate IS NULL OR e.endDate >= CURRENT_TIMESTAMP()');
                }
            } elseif ('startDate' === $sort && method_exists($referEntity, 'getStartDate') && !method_exists($referEntity, 'getEndDate')) {
                $statement->andWhere('e.startDate IS NULL OR e.startDate >= CURRENT_TIMESTAMP()');
            } elseif ('startDate' === $sort && method_exists($referEntity, 'getStartDate') && method_exists($referEntity, 'getEndDate') && !$displayPastEvents) {
                $statement->andWhere('e.startDate IS NULL OR e.startDate >= CURRENT_TIMESTAMP()')
                    ->andWhere('e.endDate IS NULL OR e.endDate <= CURRENT_TIMESTAMP()');
            }
            if (method_exists($referEntity, 'getUrls')) {
                $statement->andWhere('u.online = :online')
                    ->andWhere('u.archived = :archived')
                    ->setParameter('online', true)
                    ->setParameter('archived', false);
            }
            if (method_exists($referEntity, 'isActive')) {
                $statement->andWhere('e.active = :active')
                    ->setParameter('active', true);
            }
        }

        if ('category' !== $sort && method_exists($referEntity, 'get'.ucfirst($sort))) {
            $statement->orderBy('e.'.$sort, $order);
        }

        return $statement;
    }
}
