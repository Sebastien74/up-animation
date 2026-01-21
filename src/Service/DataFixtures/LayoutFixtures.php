<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core as CoreEntities;
use App\Entity\Layout as LayoutEntities;
use App\Entity\Module\Catalog as CatalogEntities;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Newscast as NewscastEntities;
use App\Entity\Module\Portfolio as PortfolioEntities;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * LayoutFixtures.
 *
 * Layout Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LayoutFixtures::class, 'key' => 'layout_fixtures'],
])]
class LayoutFixtures
{
    private array $blockTypes;
    private array $modules;

    /**
     * LayoutFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->blockTypes = $this->entityManager->getRepository(LayoutEntities\BlockType::class)->findAll();
        $this->modules = $this->entityManager->getRepository(CoreEntities\Module::class)->findAll();
    }

    /**
     * Add entity LayoutConfiguration.
     */
    public function add(CoreEntities\Configuration $configuration, bool $devMode, array $defaultsModules, array $othersModules, ?User $user = null, ?CoreEntities\Website $websiteToDuplicate = null): void
    {
        if ($websiteToDuplicate instanceof CoreEntities\Website) {
            $this->addDbLayouts($configuration, $websiteToDuplicate, $user);
        } else {
            $this->addLayouts($configuration, $devMode, $defaultsModules, $othersModules, $user);
        }
    }

    /**
     * To add DB LayoutConfigurations.
     */
    private function addDbLayouts(CoreEntities\Configuration $configuration, CoreEntities\Website $websiteToDuplicate, ?User $user = null): void
    {
        $layouts = $this->entityManager->getRepository(LayoutEntities\LayoutConfiguration::class)->findBy(['website' => $websiteToDuplicate]);
        foreach ($layouts as $referLayout) {
            $layoutConfiguration = new LayoutEntities\LayoutConfiguration();
            $layoutConfiguration->setAdminName($referLayout->getAdminName());
            $layoutConfiguration->setEntity($referLayout->getEntity());
            $layoutConfiguration->setPosition($referLayout->getPosition());
            $layoutConfiguration->setWebsite($configuration->getWebsite());
            $layoutConfiguration->setCreatedBy($user);
            foreach ($referLayout->getBlockTypes() as $blockType) {
                $layoutConfiguration->addBlockType($blockType);
            }
            foreach ($referLayout->getModules() as $module) {
                $layoutConfiguration->addModule($module);
            }
            $this->entityManager->persist($layoutConfiguration);
        }
    }

    /**
     * To add LayoutConfigurations.
     */
    private function addLayouts(CoreEntities\Configuration $configuration, bool $devMode, array $defaultsModules, array $othersModules, ?User $user = null): void
    {
        $configurationBlockTypes = $this->getBlockTypesIds($configuration);
        $layoutConfigurations = $this->getConfiguration($devMode, $defaultsModules, $othersModules);
        $position = 1;
        foreach ($layoutConfigurations as $classname => $config) {
            $layoutConfiguration = new LayoutEntities\LayoutConfiguration();
            $layoutConfiguration->setAdminName($config->adminName);
            $layoutConfiguration->setEntity($classname);
            $layoutConfiguration->setPosition($position);
            $layoutConfiguration->setWebsite($configuration->getWebsite());
            $layoutConfiguration->setCreatedBy($user);
            ++$position;
            foreach ($this->blockTypes as $blockType) {
                if (in_array($blockType->getCategory(), $config->blocks) && in_array($blockType->getId(), $configurationBlockTypes)) {
                    $layoutConfiguration->addBlockType($blockType);
                }
            }
            foreach ($this->modules as $module) {
                if (in_array($module->getRole(), $config->modules)) {
                    $layoutConfiguration->addModule($module);
                }
            }
            $this->entityManager->persist($layoutConfiguration);
        }
    }

    /**
     * Get configuration BlockType ids.
     */
    private function getBlockTypesIds(CoreEntities\Configuration $configuration): array
    {
        $configurationBlockTypes = [];
        foreach ($configuration->getBlockTypes() as $blockType) {
            $configurationBlockTypes[] = $blockType->getId();
        }

        return $configurationBlockTypes;
    }

    /**
     * Get configuration.
     */
    private function getConfiguration(bool $devMode, array $defaultsModules, array $othersModules): array
    {
        $fullDefaultModules = $devMode ? array_merge($defaultsModules, $othersModules) : ['ROLE_NEWSCAST', 'ROLE_FORM', 'ROLE_SLIDER'];

        return [
            LayoutEntities\Page::class => (object) [
                'adminName' => 'Page',
                'blocks' => ['content', 'global'],
                'modules' => array_merge(['ROLE_CONTACT', 'ROLE_SITE_MAP', 'ROLE_PAGE'], $fullDefaultModules),
            ],
            NewscastEntities\Category::class => (object) [
                'adminName' => "Catégorie d'actualité",
                'blocks' => ['layout', 'global'],
                'modules' => [],
            ],
            NewscastEntities\Newscast::class => (object) [
                'adminName' => 'Fiche actualité',
                'blocks' => ['content', 'global'],
                'modules' => $fullDefaultModules,
            ],
            CatalogEntities\Catalog::class => (object) [
                'adminName' => 'Catalogue',
                'blocks' => ['content', 'global', 'layout', 'layout-catalog'],
                'modules' => [],
            ],
            CatalogEntities\Product::class => (object) [
                'adminName' => 'Fiche produit',
                'blocks' => ['content', 'global', 'layout', 'layout-catalog'],
                'modules' => $fullDefaultModules,
            ],
            Form::class => (object) [
                'adminName' => 'Formulaire',
                'blocks' => ['form'],
                'modules' => [],
            ],
            PortfolioEntities\Category::class => (object) [
                'adminName' => 'Catégorie de portfolio',
                'blocks' => ['layout'],
                'modules' => [],
            ],
            PortfolioEntities\Card::class => (object) [
                'adminName' => 'Fiche portfolio',
                'blocks' => ['content', 'global'],
                'modules' => $fullDefaultModules,
            ],
        ];
    }
}
