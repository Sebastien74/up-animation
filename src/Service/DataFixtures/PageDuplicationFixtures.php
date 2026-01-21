<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Form\Manager\Layout\PageDuplicateManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * PageDuplicationFixtures.
 *
 * Page Duplication Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => PageDuplicationFixtures::class, 'key' => 'page_duplication_fixtures'],
])]
class PageDuplicationFixtures
{
    /**
     * MenuFixtures constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageDuplicateManager $duplicateManager,
    ) {
    }

    /**
     * To duplicate main website pages to new website.
     *
     * @throws NonUniqueResultException
     */
    public function add(Website $website, Website $websiteToDuplicate): array
    {
        $pages = $this->entityManager->getRepository(Page::class)->findBy(['website' => $websiteToDuplicate->getId()], ['level' => 'ASC']);
        $configuration = $websiteToDuplicate->getConfiguration();
        $mainPages = [];
        foreach ($configuration->getPages() as $page) {
            $mainPages[] = $page->getSlug();
        }

        $newPages = [];
        foreach ($pages as $page) {
            $newPage = new Page();
            $newPage->setWebsite($website);
            $newPage->setSlug($page->getSlug());
            $newPage->setAdminName($page->getAdminName());
            $newPage->setAsIndex($page->isAsIndex());
            $this->duplicateManager->execute($newPage, $website, null, $page);
            foreach ($newPage->getUrls() as $url) {
                $url->setOnline(true);
            }
            $newPage->setSlug($page->getSlug());
            $newPage->setPosition($page->getPosition());
            $newPage->setLevel($page->getLevel());

            $parentPage = $page->getParent();
            if ($parentPage instanceof Page) {
                $newParentPage = $this->entityManager->getRepository(Page::class)->findOneBy(['website' => $website->getId(), 'slug' => $parentPage->getSlug()]);
                $newPage->setParent($newParentPage);
            }

            $newPages[$newPage->getSlug()] = $newPage;
            $this->entityManager->persist($newPage);

            if (in_array($newPage->getSlug(), $mainPages)) {
                $configuration = $website->getConfiguration();
                $configuration->addPage($newPage);
                $this->entityManager->persist($configuration);
            }
        }

        $this->entityManager->flush();

        return $newPages;
    }
}
