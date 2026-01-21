<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Listing;
use App\Entity\Security\UserFront;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * NewscastListingManager.
 *
 * Manage Newscast Listing form.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => NewscastListingManager::class, 'key' => 'module_newscast_listing_form_manager'],
])]
class NewscastListingManager
{
    /**
     * NewscastManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws Exception
     */
    public function preUpdate(Listing $listing, Website $website, array $interface = [], ?Form $form = null, ?UserFront $userFront = null): void
    {
        $asEvent = $listing->isAsEvents();
        $listingCategoriesCount = $listing->getCategories()->count();
        $categoriesCount = 0;
        foreach ($listing->getCategories() as $category) {
            if ($category->isAsEvents()) {
                ++$categoriesCount;
            }
        }

        if ($categoriesCount === $listingCategoriesCount && $categoriesCount > 1) {
            $asEvent = true;
            $listing->setAsEvents(true);
            $listing->setOrderBy('startDate-asc');
        }

        if (!$listing->isAsEvents() && str_contains($listing->getOrderBy(), 'Date')) {
            $matches = explode('-', $listing->getOrderBy());
            $listing->setOrderBy('publicationStart-'.end($matches));
        }

        if ($asEvent) {
            $repository = $this->coreLocator->em()->getRepository(Category::class);
            $eventCategory = $repository->findOneBy(['slug' => 'event', 'website' => $website]);
            if (!$eventCategory instanceof Category) {
                $position = count($repository->findBy(['website' => $website])) + 1;
                $category = new Category();
                $category->setAdminName('Évènement');
                $category->setSlug('event');
                $category->setWebsite($website);
                $category->setOrderBy('startDate-desc');
                $category->setPosition($position);
                $category->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $listing->addCategory($category);
            }
        }

        if ($listing->isAsEvents() && !str_contains($listing->getOrderBy(), 'startDate')) {
            $listing->setOrderBy('startDate-asc');
        }
    }
}
