<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\Layout;
use App\Form\Interface\LayoutFormManagerInterface;
use App\Form\Manager\Core\BaseDuplicateManager;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * LayoutDuplicateManager.
 *
 * Manage admin Layout duplication form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LayoutDuplicateManager::class, 'key' => 'layout_duplicate_form_manager'],
])]
class LayoutDuplicateManager extends BaseDuplicateManager
{
    private \Doctrine\ORM\EntityRepository $repository;

    /**
     * LayoutDuplicateManager constructor.
     */
    public function __construct(
        private readonly LayoutFormManagerInterface $layoutManager,
        protected string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        protected EntityManagerInterface $entityManager,
        protected Uploader $uploader,
        protected RequestStack $requestStack,
    ) {
        $this->repository = $entityManager->getRepository(Layout::class);

        parent::__construct($projectDir, $coreLocator, $entityManager, $uploader, $requestStack);
    }

    /**
     * Execute duplication.
     */
    public function execute(Layout $layout, Website $website, Form $form)
    {
    }

    /**
     * Duplicate Layout.
     */
    public function addLayout(Layout $layout, Layout $layoutToDuplicate, Website $website): void
    {
        $layout->setWebsite($website);
        $layout->setPosition($this->getPosition($website));
        $this->entityManager->persist($layout);
        $this->layoutManager->zoneDuplicate()->addZones($layout, $layoutToDuplicate->getZones(), $website);
    }

    /**
     * Get new position.
     */
    private function getPosition(Website $website): int
    {
        return count($this->repository->findBy(['website' => $website])) + 1;
    }
}
