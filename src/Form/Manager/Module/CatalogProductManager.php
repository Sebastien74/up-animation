<?php

declare(strict_types=1);

namespace App\Form\Manager\Module;

use App\Entity\Core\Website;
use App\Entity\Information\Address;
use App\Entity\Module\Catalog as CatalogEntities;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Form\Interface\CoreFormManagerInterface;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * CatalogProductManager.
 *
 * Manage Product in admin
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CatalogProductManager::class, 'key' => 'module_catalog_product_form_manager'],
])]
class CatalogProductManager
{
    private const bool ENABLED_DRAG = false;

    /**
     * CatalogProductManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CoreFormManagerInterface $baseCoreLocator,
    ) {
    }

    /**
     * To get ENABLED_DRAG var.
     */
    public function isDraggable(): bool
    {
        return self::ENABLED_DRAG;
    }

    /**
     * @prePersist
     *
     * @throws MappingException
     */
    public function prePersist(CatalogEntities\Product $product, Website $website, array $interface, Form $form): void
    {
        $this->baseCoreLocator->base()->prePersist($product, $website);

        $this->setInformation($product);
        if (in_array('lots', $product->getCatalog()->getTabs())) {
            $this->setLots($website, $product);
        }
    }

    /**
     * @preUpdate
     */
    public function preUpdate(CatalogEntities\Product $product, Website $website, array $interface, Form $form): void
    {
        $catalogBeforePostId = intval($this->coreLocator->request()->get('product')['catalogBeforePost']);
        /** @var CatalogEntities\Catalog $catalogBeforePost */
        $catalogBeforePost = $this->coreLocator->em()->getRepository(CatalogEntities\Catalog::class)->find($catalogBeforePostId);
        $currentCatalog = $product->getCatalog();

        if ($currentCatalog->getId() !== $catalogBeforePost->getId()) {
            $this->setPositions($product, $currentCatalog, $catalogBeforePost);
        }

        $this->setValues($product, $website, $form);
        $this->setInformation($product);

        if (in_array('lots', $product->getCatalog()->getTabs())) {
            $this->setLots($website, $product);
        }

        if ($product->getCategories()->count() >= 1 && !$product->getMainCategory()) {
            $product->setMainCategory($product->getCategories()->first());
        }

        $this->coreLocator->em()->persist($product);
    }

    /**
     * Set Lots.
     */
    private function setLots(Website $website, CatalogEntities\Product $product): void
    {
        $position = count($this->coreLocator->em()->getRepository(CatalogEntities\Lot::class)->findBy(['product' => $product])) + 1;

        foreach ($website->getConfiguration()->getAllLocales() as $locale) {
            foreach ($product->getLots() as $lot) {
                $existing = false;
                foreach ($lot->getIntls() as $intl) {
                    if ($intl->getLocale() === $locale) {
                        $existing = true;
                    }
                }
                if (!$existing) {
                    $intl = new CatalogEntities\LotIntl();
                    $intl->setLocale($locale);
                    $intl->setWebsite($website);
                    $intl->setLot($lot);
                    $lot->addIntl($intl);
                }
                if (!$lot->getPosition()) {
                    $lot->setPosition($position);
                    ++$position;
                }
                $this->coreLocator->em()->persist($lot);
            }
        }
    }

    /**
     * Set Information.
     */
    private function setInformation(CatalogEntities\Product $product): void
    {
        if (!$product->getInformation()) {
            $information = new CatalogEntities\ProductInformation();
            $information->setAddress(new Address());
            $product->setInformation($information);
        }
    }

    /**
     * Set Products positions.
     */
    private function setPositions(CatalogEntities\Product $product, CatalogEntities\Catalog $currentCatalog, CatalogEntities\Catalog $catalogBeforePost): void
    {
        foreach ($catalogBeforePost->getProducts() as $beforeProduct) {
            if ($beforeProduct->getId() !== $product->getId() && $beforeProduct->getPosition() > $product->getPosition()) {
                $beforeProduct->setPosition($beforeProduct->getPosition() - 1);
                $this->coreLocator->em()->persist($beforeProduct);
            } elseif ($beforeProduct->getId() === $product->getId()) {
                $product->setCatalog($currentCatalog);
            }
        }
        $product->setPosition($currentCatalog->getProducts()->count() + 1);
    }

    /**
     * Set FeatureValueProduct.
     */
    public function setValues(CatalogEntities\Product $product, Website $website, ?Form $form = null): void
    {
        foreach ($product->getValues() as $value) {
            if (!$value->getValue() && !$value->getFeature()) {
                $product->removeValue($value);
            }
        }

        $post = $form ? $this->coreLocator->request()->get($form->getName()) : [];
        $postValues = !empty($post['values']) ? $post['values'] : [];
        $values = [];
        foreach ($postValues as $postValue) {
            $values[Urlizer::urlize($postValue['adminName']).'-'.$postValue['position']]['addToGlobal'] = isset($postValue['addToGlobal']);
        }

        foreach ($product->getValues() as $value) {
            $isCustom = false;
            $addToGlobal = $post ? $values[Urlizer::urlize($value->getAdminName()).'-'.$value->getPosition()]['addToGlobal'] : false;
            if ($value->getAdminName() && !$value->getValue() && $post) {
                $isCustom = !$addToGlobal;
                $this->setCustomizedValues($value, $website, $addToGlobal);
            }
            if (!$isCustom && $value->getValue()) {
                $value->setFeature($value->getValue()->getCatalogfeature());
            }
            if ($value->getPosition() !== $value->getFeaturePosition()) {
                $value->setPosition($value->getFeaturePosition());
            }
            if (!$value->getProduct()) {
                $value->setProduct($product);
            }
            $this->coreLocator->em()->persist($value);
        }

        if (self::ENABLED_DRAG) {
            $this->setValuesPositions($product);
        } else {
            $this->setValuesPositionsByGroups($product);
        }
        $this->setJsonValues($product);
    }

    /**
     * Set Feature values positions.
     */
    public function setValuesPositions(CatalogEntities\Product $product): void
    {
        $validEntitiesPositions = [];
        $invalidEntitiesPositions = [];
        foreach ($product->getValues() as $value) {
            if (!$value->getValue() && !$value->getFeature()) {
                $product->removeValue($value);
            } else {
                if (is_numeric($value->getPosition()) && $value->getPosition() > 0) {
                    $validEntitiesPositions[] = $value;
                } else {
                    $invalidEntitiesPositions[] = $value;
                }
            }
        }

        usort($validEntitiesPositions, fn($a, $b) => $a->getPosition() <=> $b->getPosition());
        $sortedEntities = array_merge($validEntitiesPositions, $invalidEntitiesPositions);

        $position = 1;
        foreach ($sortedEntities as $value) {
            $value->setPosition($position);
            $value->setFeaturePosition($position);
            $this->coreLocator->em()->persist($value);
            ++$position;
        }
    }

    /**
     * Set Feature values positions by group.
     */
    public function setValuesPositionsByGroups(CatalogEntities\Product $product): void
    {
        $catalogId = $product->getCatalog()->getId();

        $groups = [];
        foreach ($product->getValues() as $value) {
            $asDefault = $this->asDefaultValue($value, $catalogId, 'feature');
            $asDefault = $asDefault ?: $this->asDefaultValue($value, $catalogId, 'value');
            $keyGroup = $value->getFeature()->getPosition().'-'.$value->getFeature()->getSlug();
            if ($asDefault) {
                $key = 'as-custom' === $asDefault ? 'as-custom' : 'as-default';
                $groups[$key][$keyGroup][] = $value;
                ksort($groups[$key]);
            } elseif ($value->getFeature()) {
                $groups[$keyGroup][] = $value;
            }
        }
        ksort($groups);

        $position = 1;

        foreach (['as-default', 'as-custom'] as $key) {
            if (!empty($groups[$key])) {
                foreach ($groups[$key] as $group) {
                    foreach ($group as $value) {
                        $value->setPosition($position);
                        $value->setFeaturePosition($position);
                        $this->coreLocator->em()->persist($value);
                        ++$position;
                    }
                }
            }
        }

        foreach ($groups as $key => $group) {
            if ('as-default' !== $key && 'as-custom' !== $key) {
                foreach ($group as $value) {
                    $value->setPosition($position);
                    $value->setFeaturePosition($position);
                    $this->coreLocator->em()->persist($value);
                    ++$position;
                }
            }
        }
    }

    /**
     * To check if is default Feature or Value.
     */
    private function asDefaultValue(FeatureValueProduct $value, int $catalogId, string $property): bool|string
    {
        $method = 'get'.ucfirst($property);
        if ($value->$method()) {
            foreach ($value->$method()->getCatalogs() as $catalog) {
                if ($catalog->getId() == $catalogId) {
                    if ($value->getValue() && $value->getValue()->getProduct()) {
                        return 'as-custom';
                    }
                    return true;
                }
            }
            if ($value->getValue() && $value->getValue()->getProduct()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set JsonValues.
     */
    public function setJsonValues(CatalogEntities\Product $product): void
    {
        $jsonData = [];
        foreach ($product->getValues() as $value) {
            $featureDefault = false;
            $feature = $value->getFeature();
            if ($feature) {
                foreach ($feature->getCatalogs() as $catalog) {
                    if ($catalog->getId() === $product->getCatalog()->getId()) {
                        $featureDefault = true;
                        break;
                    }
                }
            }
            $valueDefault = false;
            $mainValue = $value->getValue();
            if ($mainValue) {
                foreach ($mainValue->getCatalogs() as $catalog) {
                    if ($catalog->getId() === $product->getCatalog()->getId()) {
                        $valueDefault = true;
                        break;
                    }
                }
            }
            $asDefault = $featureDefault || $valueDefault;
            $value->setAsDefault($asDefault);
            $jsonData[$value->getPosition()] = [
                'feature' => $feature?->getId(),
                'featureName' => $feature?->getAdminName(),
                'featureDefault' =>$featureDefault,
                'value' => $mainValue?->getId(),
                'valueName' => $mainValue?->getAdminName(),
                'valueDefault' => $valueDefault,
                'valueProduct' => $value->getId(),
                'valueProductDefault' => $value->isAsDefault(),
                'displayInArray' => $value->isDisplayInArray(),
                'position' => $value->getPosition(),
            ];
        }

        $product->setJsonValues($jsonData);
    }

    /**
     * Set FeatureValueProduct has customized.
     */
    private function setCustomizedValues(CatalogEntities\FeatureValueProduct $value, Website $website, bool $addToGlobal): void
    {
        if ($value->getFeature() instanceof CatalogEntities\Feature) {

            $newValue = new CatalogEntities\FeatureValue();
            $newValue->setWebsite($website);
            $newValue->setCatalogfeature($value->getFeature());
            $newValue->setAdminName($value->getAdminName());

            if (!$addToGlobal) {
                $newValue->setCustomized(true);
                $newValue->setProduct($value->getProduct());
            }

            foreach ($website->getConfiguration()->getAllLocales() as $locale) {
                $intl = new CatalogEntities\FeatureValueIntl();
                $intl->setTitle($value->getAdminName());
                $intl->setFeatureValue($newValue);
                $intl->setLocale($locale);
                $intl->setWebsite($website);
                $newValue->addIntl($intl);
            }

            $value->setValue($newValue);
            $this->coreLocator->em()->persist($newValue);
            $this->coreLocator->em()->persist($value);
        }
    }
}
