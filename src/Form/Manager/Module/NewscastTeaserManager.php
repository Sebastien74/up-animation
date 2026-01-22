<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Listing;
use App\Entity\Module\Newscast\Teaser;
use App\Entity\Security\UserFront;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * NewscastTeaserManager.
 *
 * Manage Newscast Listing form.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => NewscastTeaserManager::class, 'key' => 'module_newscast_teaser_form_manager'],
])]
class NewscastTeaserManager
{
    /**
     * NewscastTeaserManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @prePersist
     *
     * @throws Exception
     */
    public function preUpdate(Teaser $teaser, Website $website, array $interface = [], ?Form $form = null, ?UserFront $userFront = null): void
    {
        $teaser->setItemsPerSlide(4);

        $asEventCategory = false;
        foreach ($teaser->getCategories() as $category) {
            if ($category->isAsEvents()) {
                $asEventCategory = true;
                $teaser->setAsEvents(true);
                $teaser->setOrderBy('startDate-asc');
                break;
            }
        }

        if (!$asEventCategory && $teaser->isAsEvents()) {
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
                $category->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                $teaser->addCategory($category);
            }
        }

        if ($teaser->isAsEvents() && !str_contains($teaser->getOrderBy(), 'startDate')) {
            $teaser->setOrderBy('startDate-asc');
        }
    }
}
