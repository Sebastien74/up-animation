<?php

declare(strict_types=1);

namespace App\Twig\Core;

use App\Entity\Core;
use App\Model\Core\ConfigurationModel;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * WebsiteRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteRuntime implements RuntimeExtensionInterface
{
    private array $cache = [];

    /**
     * WebsiteRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get current website.
     */
    public function website(): ?WebsiteModel
    {
        return $this->coreLocator->website();
    }

    /**
     * Get current WebsiteModel request ID.
     */
    public function websiteId(): ?int
    {
        return $this->website()->id;
    }

    /**
     * Get default domain name by locale.
     */
    public function domain(string $locale, ?WebsiteModel $website = null): bool|string
    {
        $protocol = $_ENV['APP_PROTOCOL'].'://';
        $website = $website instanceof WebsiteModel ? $website : $this->website();
        $configuration = $website->configuration;
        $domains = $this->coreLocator->em()->getRepository(Core\Domain::class)->findBy([
            'configuration' => $configuration->entity,
            'asDefault' => true,
        ]);

        $defaultDomain = false;
        foreach ($domains as $domain) {
            if ($domain->getLocale() === $locale) {
                return $protocol.$domain->getName();
            }
            if ($domain->getLocale() === $configuration->locale) {
                $defaultDomain = $protocol.$domain->getName();
            }
        }

        return $defaultDomain;
    }

    /**
     * Get entity interface.
     *
     * @throws NonUniqueResultException
     */
    public function interface(string $classname): array
    {
        $interfaceHelper = $this->coreLocator->interfaceHelper();
        $interfaceHelper->setInterface($classname);

        return $interfaceHelper->getInterface();
    }

    /**
     * Get entity interface name.
     */
    public function interfaceName(mixed $class = null): ?string
    {
        $classname = is_object($class) ? get_class($class) : $class;
        if ($classname) {
            $entity = new $classname();
            if (method_exists($entity, 'getInterface')) {
                return !empty($entity::getInterface()['name']) ? $entity::getInterface()['name'] : null;
            }
        }

        return null;
    }

    /**
     * Check if module is active.
     */
    public function moduleActive(string $moduleCode, ?ConfigurationModel $configuration = null, bool $object = false): mixed
    {
        $configuration = !empty($cache['configuration']) ? $cache['configuration'] : $configuration;
        if (!$configuration) {
            $configuration = $this->coreLocator->website()->configuration;
        }
        $modules = $configuration->modules;
        if (isset($modules[$moduleCode])) {
            return $object ? $this->coreLocator->em()->getRepository(Core\Module::class)->findOneBySlug($moduleCode) : $modules[$moduleCode];
        }

        return false;
    }

    /**
     * Get app colors by category.
     */
    public function appColors(WebsiteModel $website, string $category): string
    {
        $colors = '#000000, Noir, #ffffff, Blanc,';
        foreach ($website->configuration->entity->getColors() as $color) {
            if ($color->getCategory() === $category && $color->isActive() && !str_contains($colors, $color->getColor())) {
                $colors .= $color->getColor().', '.$color->getAdminName().', ';
            }
        }

        return rtrim($colors, ', ');
    }

    /**
     * Get all websites.
     */
    public function allWebsites(bool $onlyActive = false): array
    {
        if ($onlyActive) {
            return $this->coreLocator->em()->getRepository(Core\Website::class)->findActives();
        }

        return $this->coreLocator->em()->getRepository(Core\Website::class)->findAllWebsites();
    }

    /**
     * Check if is multi websites' app.
     */
    public function isMultiWebsite(bool $onlyActive = false): bool
    {
        return count($this->allWebsites($onlyActive)) > 1;
    }
}
