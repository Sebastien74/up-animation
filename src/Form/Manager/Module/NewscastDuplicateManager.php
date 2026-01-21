<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Entity\Module\Newscast\Newscast;
use App\Entity\Seo\Url;
use App\Form\Manager\Core\BaseDuplicateManager;
use App\Form\Manager\Layout\LayoutDuplicateManager;
use App\Form\Manager\Layout\LayoutManager;
use App\Form\Manager\Seo\UrlManager;
use App\Repository\Module\Newscast\NewscastRepository;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * NewscastDuplicateManager.
 *
 * Manage admin Newscast duplication form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => NewscastDuplicateManager::class, 'key' => 'module_newscast_duplicate_form_manager'],
])]
class NewscastDuplicateManager extends BaseDuplicateManager
{
    protected ?Website $website = null;
    private NewscastRepository $repository;

    /**
     * NewscastDuplicateManager constructor.
     */
    public function __construct(
        string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        EntityManagerInterface $entityManager,
        Uploader $uploader,
        RequestStack $requestStack,
        private readonly LayoutDuplicateManager $layoutDuplicateManager,
        private readonly UrlManager $urlManager,
        private readonly LayoutManager $layoutManager)
    {
        $this->repository = $entityManager->getRepository(Newscast::class);

        parent::__construct($projectDir, $coreLocator, $entityManager, $uploader, $requestStack);
    }

    /**
     * Duplicate Newscast.
     *
     * @throws NonUniqueResultException
     */
    public function execute(Newscast $newscast, Website $website, Form $form): void
    {
        /** @var Newscast $newscastToDuplicate */
        $newscastToDuplicate = $form->get('newscast')->getData();
        $duplicateToWebsite = $newscast->getWebsite();

        $session = new Session();
        $session->set('DUPLICATE_TO_WEBSITE', $duplicateToWebsite);

        $this->setNewscast($newscast, $newscastToDuplicate, $duplicateToWebsite);
        $this->addLayout($newscast, $newscastToDuplicate->getLayout(), $duplicateToWebsite);
        $this->addUrls($newscast, $duplicateToWebsite);
        $this->addMediaRelations($newscast, $newscastToDuplicate->getMediaRelations());

        $this->entityManager->persist($newscast);
        $this->entityManager->flush();

        $this->layoutManager->setGridZone($newscast->getLayout());

        $session->remove('DUPLICATE_TO_WEBSITE');
    }

    /**
     * Set Newscast.
     */
    private function setNewscast(Newscast $newscast, Newscast $newscastToDuplicate, Website $website): void
    {
        $position = count($this->repository->findBy(['website' => $website])) + 1;
        $newscast->setPosition($position);
        $newscast->setWebsite($website);
        $newscast->setCategory($newscastToDuplicate->getCategory());
        $newscast->setCustomLayout(true);
    }

    /**
     * Add Layout.
     */
    private function addLayout(Newscast $newscast, Layout $layoutToDuplicate, Website $website): void
    {
        $layout = new Layout();
        $layout->setAdminName($newscast->getAdminName());

        $newscast->setLayout($layout);

        $this->layoutDuplicateManager->addLayout($layout, $layoutToDuplicate, $website);

        $this->entityManager->persist($layout);
        $this->entityManager->persist($newscast);
    }

    /**
     * Add Url.
     *
     * @throws NonUniqueResultException
     */
    private function addUrls(Newscast $newscast, Website $website): void
    {
        $locales = $website->getConfiguration()->getAllLocales();
        foreach ($locales as $locale) {
            $url = new Url();
            $url->setLocale($locale);
            $this->urlManager->addUrl($url, $website, $newscast);
            $newscast->addUrl($url);
        }
    }
}
