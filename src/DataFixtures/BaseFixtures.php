<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BaseFixtures.
 *
 * Base Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
abstract class BaseFixtures extends Fixture
{
    protected TranslatorInterface $translator;
    protected ?string $projectDir;
    protected string $locale;
    protected ObjectManager $manager;

    /**
     * BaseFixtures constructor.
     */
    public function __construct(protected CoreLocatorInterface $coreLocator)
    {
        $this->translator = $coreLocator->translator();
        $this->projectDir = $coreLocator->projectDir();
        $this->locale = $this->coreLocator->locale();
    }

    /**
     * Load data.
     */
    abstract protected function loadData(ObjectManager $manager): void;

    /**
     * Load.
     */
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->loadData($manager);
    }
}
