<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Layout\BlockType;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\DataFixturesInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

/**
 * WebsiteFixtures.
 *
 * WebsiteModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private array $descriptions = [];

    /**
     * WebsiteFixtures constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        private readonly DataFixturesInterface $fixtures,
    ) {
        parent::__construct($coreLocator);
    }

    /**
     * loadData.
     *
     * @throws \Exception
     */
    protected function loadData(ObjectManager $manager): void
    {
        $descriptionDirname = $this->projectDir.'/bin/data/fixtures/extensions.yaml';
        $descriptionDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $descriptionDirname);
        $this->descriptions = Yaml::parseFile($descriptionDirname);

        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        $website = new Website();

        $this->fixtures->website()->initialize($website, $this->locale, $user);

        $website->setAdminName('Site principal');
        $website->setSlug('default');

        $configuration = $website->getConfiguration();
        $configuration->setAsDefault(true);

        $manager->persist($website);
        $manager->flush();

        $this->addReference('website', $website);
        $this->addReference('configuration', $configuration);

        $this->addBlocksTypesIntls($manager, $website);
        $this->addModulesIntls($manager, $website);
    }

    /**
     * Add Modules intl.
     *
     * @throws MappingException
     */
    private function addBlocksTypesIntls(ObjectManager $manager, Website $website): void
    {
        $blocksTypes = $manager->getRepository(BlockType::class)->findAll();
        foreach ($blocksTypes as $blockType) {
            $this->addIntl($manager, $website, 'blocks_types', $blockType);
        }
    }

    /**
     * Add Modules intl.
     *
     * @throws MappingException
     */
    private function addModulesIntls(ObjectManager $manager, Website $website): void
    {
        $modules = $manager->getRepository(Module::class)->findAll();
        foreach ($modules as $module) {
            $this->addIntl($manager, $website, 'modules', $module);
        }
    }

    /**
     * Add Intl.
     *
     * @throws MappingException
     */
    private function addIntl(ObjectManager $manager, Website $website, string $type, $entity): void
    {
        $descriptions = !empty($this->descriptions[$type][$entity->getSlug()])
            ? $this->descriptions[$type][$entity->getSlug()]
            : [];

        $asAdvert = $descriptions['advert'] ?? false;
        $entity->setInAdvert($asAdvert);

        foreach ($descriptions as $local => $fields) {
            if ('advert' !== $local) {
                $intlData = $this->coreLocator->metadata($entity, 'intls');
                $intl = new ($intlData->targetEntity)();
                $intl->setLocale($local);
                $intl->setWebsite($website);
                if (method_exists($intl, $intlData->setter)) {
                    $setter = $intlData->setter;
                    $intl->$setter($entity);
                }
                $entity->addIntl($intl);
                foreach ($fields as $property => $value) {
                    $setter = 'set'.ucfirst($property);
                    if (method_exists($intl, $setter)) {
                        $intl->$setter($value);
                    }
                }
            }
        }

        if (0 === $entity->getIntls()->count()) {
            $intlData = $this->coreLocator->metadata($entity, 'intls');
            $intl = new ($intlData->targetEntity)();
            $intl->setWebsite($website);
            if (method_exists($intl, $intlData->setter)) {
                $setter = $intlData->setter;
                $intl->$setter($entity);
            }
            $intl->setLocale($this->locale);
            $entity->addIntl($intl);
        }

        $manager->persist($entity);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
            BlockTypeFixtures::class,
            ActionFixtures::class,
        ];
    }
}
