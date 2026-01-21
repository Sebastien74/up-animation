<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Layout;
use App\Form\Interface\LayoutFormManagerInterface;
use App\Form\Manager\Core\BaseDuplicateManager;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * ColDuplicateManager.
 *
 * Manage admin Col duplication form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ColDuplicateManager::class, 'key' => 'layout_col_duplicate_form_manager'],
])]
class ColDuplicateManager extends BaseDuplicateManager
{
    /**
     * ColDuplicateManager constructor.
     */
    public function __construct(
        private readonly LayoutFormManagerInterface $layoutManager,
        protected string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        protected EntityManagerInterface $entityManager,
        protected Uploader $uploader,
        protected RequestStack $requestStack,
    ) {
        parent::__construct($projectDir, $coreLocator, $entityManager, $uploader, $requestStack);
    }

    /**
     * Duplicate Cols.
     */
    public function addCols(Layout\Zone $zone, Collection $colsToDuplicate): void
    {
        foreach ($colsToDuplicate as $colToDuplicate) {
            /** @var Layout\Col $colToDuplicate */
            $col = new Layout\Col();
            $this->setByProperties($col, $colToDuplicate);
            $col->setZone($zone);
            $this->entityManager->persist($col);
            $this->entityManager->flush();
            $this->entityManager->refresh($col);
            $this->layoutManager->blockDuplicate()->addBlocks($col, $colToDuplicate->getBlocks());
        }
    }
}
