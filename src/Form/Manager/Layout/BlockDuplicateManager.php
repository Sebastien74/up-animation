<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Layout;
use App\Form\Manager\Core\BaseDuplicateManager;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * BlockDuplicateManager.
 *
 * Manage admin Block duplication form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BlockDuplicateManager::class, 'key' => 'layout_block_duplicate_form_manager'],
])]
class BlockDuplicateManager extends BaseDuplicateManager
{
    /**
     * BlockDuplicateManager constructor.
     */
    public function __construct(
        protected string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        protected EntityManagerInterface $entityManager,
        protected Uploader $uploader,
        protected RequestStack $requestStack,
    ) {
        parent::__construct($projectDir, $coreLocator, $entityManager, $uploader, $requestStack);
    }

    /**
     * Duplicate Blocks.
     */
    public function addBlocks(Layout\Col $col, Collection $blocksToDuplicate): void
    {
        foreach ($blocksToDuplicate as $blockToDuplicate) {
            /** @var Layout\Block $blockToDuplicate */
            $block = new Layout\Block();
            $this->setByProperties($block, $blockToDuplicate);
            $block->setCol($col);
            $this->setFieldConfiguration($blockToDuplicate, $block);
            $this->addActionIntls($block, $blockToDuplicate->getActionIntls());
            $this->entityManager->persist($block);
        }
        $this->entityManager->flush();
    }

    /**
     * Set FieldConfiguration.
     */
    private function setFieldConfiguration(Layout\Block $blockToDuplicate, Layout\Block $block): void
    {
        $fieldConfiguration = $blockToDuplicate->getFieldConfiguration();

        if ($fieldConfiguration) {
            $configuration = new Layout\FieldConfiguration();
            $configuration->setConstraints($fieldConfiguration->getConstraints());
            $configuration->setPreferredChoices($fieldConfiguration->getPreferredChoices());
            $configuration->setRequired($fieldConfiguration->isRequired());
            $configuration->setMultiple($fieldConfiguration->isMultiple());
            $configuration->setExpanded($fieldConfiguration->isExpanded());
            $configuration->setPicker($fieldConfiguration->isPicker());
            $configuration->setRegex($fieldConfiguration->getRegex());
            $configuration->setMin($fieldConfiguration->getMin());
            $configuration->setMax($fieldConfiguration->getMax());
            $configuration->setMaxFileSize($fieldConfiguration->getMaxFileSize());
            $configuration->setFilesTypes($fieldConfiguration->getFilesTypes());
            $configuration->setButtonType($fieldConfiguration->getButtonType());
            $configuration->setBlock($block);

            foreach ($fieldConfiguration->getFieldValues() as $fieldValueToDuplicate) {
                $fieldValue = new Layout\FieldValue();
                $fieldValue->setAdminName($fieldValueToDuplicate->getAdminName());
                $fieldValue->setConfiguration($configuration);
                $this->addIntls($fieldValue, $fieldValueToDuplicate->getIntls());
                $this->entityManager->persist($fieldValue);
            }

            $block->setFieldConfiguration($configuration);
            $this->entityManager->persist($block);
        }
    }

    /**
     * Add ActionIntl[].
     */
    private function addActionIntls(Layout\Block $block, Collection $actionsToDuplicate): void
    {
        $websiteToDuplicate = $block->getCol()->getZone()->getLayout()->getWebsite();

        foreach ($actionsToDuplicate as $actionToDuplicate) {
            /** @var Layout\ActionIntl $actionToDuplicate */
            $actionFilter = $actionToDuplicate->getActionFilter();
            $actionIntl = new Layout\ActionIntl();
            $actionIntl->setLocale($actionToDuplicate->getLocale());
            $websiteOrigin = $actionToDuplicate->getBlock()->getCol()->getZone()->getLayout()->getWebsite();

            if ($websiteOrigin->getId() !== $websiteToDuplicate->getId()
                && $actionFilter
                && $block->getAction() instanceof Layout\Action) {
                $classname = $block->getAction()->getEntity();
                $repository = $this->entityManager->getRepository($classname);
                $originAction = $repository->find($actionFilter);
                if (is_object($originAction) && method_exists($originAction, 'getSlug') && method_exists($originAction, 'getWebsite')) {
                    $duplicateActions = $repository->findBy(['slug' => $originAction->getSlug(), 'website' => $websiteToDuplicate]);
                    $duplicateAction = !empty($duplicateActions[0]) ? $duplicateActions[0] : null;
                    if (is_object($duplicateAction) && method_exists($originAction, 'getId')) {
                        $actionFilter = $duplicateAction->getId();
                    }
                }
            }

            $actionIntl->setActionFilter($actionFilter);
            $block->addActionIntl($actionIntl);
            $this->entityManager->persist($actionIntl);
        }
    }
}
