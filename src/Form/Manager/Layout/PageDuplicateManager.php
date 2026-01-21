<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Seo\Url;
use App\Form\Interface\LayoutFormManagerInterface;
use App\Form\Manager\Core\BaseDuplicateManager;
use App\Form\Manager\Seo\UrlManager;
use App\Repository\Layout\PageRepository;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * PageDuplicateManager.
 *
 * Manage admin Page duplication form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => PageDuplicateManager::class, 'key' => 'layout_page_duplicate_form_manager'],
])]
class PageDuplicateManager extends BaseDuplicateManager
{
    protected ?Website $website = null;
    private PageRepository $repository;

    /**
     * PageDuplicateManager constructor.
     */
    public function __construct(
        private readonly LayoutFormManagerInterface $layoutManager,
        private readonly UrlManager $urlManager,
        protected string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        protected EntityManagerInterface $entityManager,
        protected Uploader $uploader,
        protected RequestStack $requestStack,
    ) {
        $this->repository = $entityManager->getRepository(Layout\Page::class);

        parent::__construct($projectDir, $coreLocator, $entityManager, $uploader, $requestStack);
    }

    /**
     * Duplicate Page.
     *
     * @throws NonUniqueResultException
     */
    public function execute(Layout\Page $page, Website $website, ?Form $form = null, ?Layout\Page $pageToDuplicate = null): void
    {
        /** @var Layout\Page $pageToDuplicate */
        $pageToDuplicate = $form instanceof Form ? $form->get('page')->getData() : $pageToDuplicate;
        $duplicateToWebsite = $page->getWebsite() instanceof Website ? $page->getWebsite() : $website;

        $session = new Session();
        $session->set('DUPLICATE_TO_WEBSITE', $duplicateToWebsite);

        $this->setPage($pageToDuplicate, $page, $duplicateToWebsite);
        $this->addLayout($page, $pageToDuplicate->getLayout(), $duplicateToWebsite);
        $this->addUrls($page, $duplicateToWebsite);
        $this->addMediaRelations($page, $pageToDuplicate->getMediaRelations());

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $this->layoutManager->layout()->setGridZone($page->getLayout());

        $session->remove('DUPLICATE_TO_WEBSITE');
    }

    /**
     * Set Page.
     */
    private function setPage(Layout\Page $pageToDuplicate, Layout\Page $page, Website $website): void
    {
        $parentPage = $page->getParent();
        $page->setBackgroundColor($pageToDuplicate->getBackgroundColor());
        $page->setTemplate($pageToDuplicate->getTemplate());
        $page->setWebsite($website);
        $page->setLevel($this->getLevel($parentPage));
        $page->setPosition($this->getPosition($website, $parentPage));
    }

    /**
     * Add Layout.
     */
    private function addLayout(Layout\Page $page, Layout\Layout $layoutToDuplicate, Website $website): void
    {
        $layout = new Layout\Layout();
        $layout->setAdminName($page->getAdminName());
        $page->setLayout($layout);
        $this->layoutManager->layoutDuplicate()->addLayout($layout, $layoutToDuplicate, $website);
        $this->entityManager->persist($layout);
        $this->entityManager->persist($page);
    }

    /**
     * Add Url.
     *
     * @throws NonUniqueResultException
     */
    private function addUrls(Layout\Page $page, Website $website): void
    {
        $locales = $website->getConfiguration()->getAllLocales();
        foreach ($locales as $locale) {
            $url = new Url();
            $url->setLocale($locale);
            $this->urlManager->addUrl($url, $website, $page);
            $page->addUrl($url);
        }
    }

    /**
     * Get level.
     */
    private function getLevel(?Layout\Page $parentPage = null): int
    {
        return $parentPage ? $parentPage->getLevel() + 1 : 1;
    }

    /**
     * Get new position.
     */
    private function getPosition(Website $website, ?Layout\Page $parentPage = null): int
    {
        return $parentPage
            ? count($parentPage->getPages()) + 1
            : count($this->repository->findBy([
                'website' => $website,
                'level' => 1,
            ])) + 1;
    }
}
