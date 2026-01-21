<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Module\Catalog\Feature;
use App\Model\Layout\BlockModel;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * BlockManager.
 *
 * Manage admin Block form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BlockManager::class, 'key' => 'layout_block_form_manager'],
])]
class BlockManager
{
    private string $blockType;

    /**
     * BlockManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @preUpdate
     *
     * @throws NonUniqueResultException|MappingException
     */
    public function preUpdate(Layout\Block $block, Website $website, array $interface, Form $form): void
    {
        $blockTypeSlug = $block->getBlockType() instanceof Layout\BlockType ? $block->getBlockType()->getSlug() : null;
        $setter = 'set'.ucfirst(str_replace('-', '', $blockTypeSlug));
        if (method_exists($this, $setter)) {
            $this->$setter($block, $website);
        }
        $this->setListing($block);
        $this->setFieldConfiguration($block, $website, $interface, $form);
        $this->setFeature($block);

        $fieldConfiguration = $block->getFieldConfiguration();
        if ($fieldConfiguration) {
            $intl = BlockModel::fromEntity($block, $this->coreLocator)->intl;
            if ($intl->title && $block->getAdminName() !== $intl->title) {
                $adminName = str_replace('&nbsp;', ' ', $intl->title);
                $block->setAdminName($adminName);
            }
            if (empty($fieldConfiguration->getSlug())) {
                $fieldConfiguration->setSlug($blockTypeSlug.'-'.$fieldConfiguration->getId());
                $this->coreLocator->em()->persist($fieldConfiguration);
            }
        }

        if ('card' === $blockTypeSlug) {
            foreach ($block->getIntls() as $intl) {
                if (!$intl->getTitleForce()) {
                    $intl->setTitleForce(3);
                }
            }
        }
    }

    /**
     * To set Block Media.
     */
    public function setMedias(Layout\Block $block, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $mediaRelations = $block->getMediaRelations();
        if ($configuration->isMediasSecondary()) {
            $existing = $localesMedias = [];
            foreach ($mediaRelations as $mediaRelation) {
                $existing[$mediaRelation->getLocale()][$mediaRelation->getPosition()] = true;
                $localesMedias[$mediaRelation->getLocale()][] = $mediaRelation;
            }
            foreach ($localesMedias as $localeMediaRelations) {
                if (count($localeMediaRelations) > 2) {
                    foreach ($localeMediaRelations as $key => $mediaRelation) {
                        $mediaRelation->setPosition($key  + 1);
                        $this->coreLocator->em()->persist($mediaRelation);
                        if ($mediaRelation->getPosition() > 2) {
                            $this->coreLocator->em()->remove($mediaRelation);
                        }
                    }
                    $this->coreLocator->em()->flush();
                }
            }
            for ($i = 1; $i <= 2; ++$i) {
                foreach ($configuration->getAllLocales() as $locale) {
                    if (empty($existing[$locale][$i])) {
                        $mediaRelation = new Layout\BlockMediaRelation();
                        $mediaRelation->setLocale($locale);
                        $mediaRelation->setPosition($i);
                        $block->addMediaRelation($mediaRelation);
                    }
                }
            }
        }
    }

    /**
     * To set Page in Listing.
     */
    private function setListing(Layout\Block $block): void
    {
        $action = $block->getAction();
        if ($action instanceof Layout\Action) {
            $entitiesToSet = ['Listing'];
            $classname = $action->getEntity();
            if ($classname) {
                $referEntity = new $classname();
                $matches = $action->getEntity() ? explode('\\', $classname) : [];
                $entityName = end($matches);
                if (in_array($entityName, $entitiesToSet) && method_exists($referEntity, 'setPage')) {
                    $layout = $block->getCol()->getZone()->getLayout();
                    $page = $this->coreLocator->em()->getRepository(Layout\Page::class)->findOneBy(['layout' => $layout]);
                    foreach ($block->getActionIntls() as $actionIntl) {
                        if ($actionIntl->getActionFilter()) {
                            $listing = $this->coreLocator->em()->getRepository($classname)->find($actionIntl->getActionFilter());
                            $listing->setPage($page);
                            $this->coreLocator->em()->persist($listing);
                        }
                    }
                }
            }
        }
    }

    /**
     * To get Medias for tabs form.
     */
    public function getMediaRelationsTabs(Layout\Block $block, Website $website): array
    {
        $tabs = [];
        if ($website->getConfiguration()->isMediasSecondary()) {
            foreach ($block->getMediaRelations() as $mediaRelation) {
                $tabs[$mediaRelation->getLocale()][$mediaRelation->getPosition()] = $mediaRelation;
                ksort($tabs);
                ksort($tabs[$mediaRelation->getLocale()]);
            }
        }

        return $tabs;
    }

    /**
     * Set FieldConfiguration.
     */
    private function setFieldConfiguration(Layout\Block $block, Website $website, array $interface, Form $form): void
    {
        $fieldConfiguration = $block->getFieldConfiguration();
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $this->blockType = $block->getBlockType()?->getSlug();

        if ($fieldConfiguration) {
            if (!$fieldConfiguration->getBlock()) {
                $fieldConfiguration->setBlock($block);
            }

            $fieldValuePosition = 1;
            foreach ($fieldConfiguration->getFieldValues() as $value) {
                if ($value->getId()) {
                    ++$fieldValuePosition;
                }
            }

            foreach ($fieldConfiguration->getFieldValues() as $value) {
                $value->setConfiguration($fieldConfiguration);
                if (!$value->getId()) {
                    $value->setPosition($fieldValuePosition);
                    ++$fieldValuePosition;
                }
                foreach ($configuration->getAllLocales() as $locale) {
                    $exist = $this->localeExist($value, $locale);
                    if (!$exist) {
                        $this->addIntl($website, $locale, $value, $defaultLocale);
                    }
                }
            }
        }
    }

    /**
     * To set feature.
     */
    private function setFeature(Layout\Block $block): void
    {
        $post = $this->coreLocator->request()->request->all();
        if (!empty($post['feature'])) {
            $features = [];
            $feature = !empty($post['feature']['feature']) ? $this->coreLocator->em()->getRepository(Feature::class)->find($post['feature']['feature']) : null;
            $features['featureId'] = $feature ? $feature->getId() : null;
            $block->setData($features);
        }
    }

    /**
     * Check if intl locale exist.
     */
    private function localeExist(Layout\FieldValue $value, string $locale): bool
    {
        foreach ($value->getIntls() as $existingIntl) {
            if ($existingIntl->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add intl.
     */
    private function addIntl(Website $website, string $locale, Layout\FieldValue $value, string $defaultLocale): void
    {
        $intl = new Layout\FieldValueIntl();
        $intl->setLocale($locale);
        $intl->setWebsite($website);
        if ($locale === $defaultLocale) {
            $body = 'form-emails' === $this->blockType && !str_contains($value->getAdminName(), '@')
                ? Urlizer::urlize($value->getAdminName()).'@email.com'
                : $value->getAdminName();
            $intl->setIntroduction($value->getAdminName());
            $intl->setBody($body);
        }
        $value->addIntl($intl);
    }
}
