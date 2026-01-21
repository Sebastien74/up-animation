<?php

declare(strict_types=1);

namespace App\Twig\Admin;

use App\Entity\Core\Log;
use App\Entity\Layout\BlockType;
use App\Entity\Layout\LayoutConfiguration;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog\Feature;
use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Kernel;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CoreRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CoreRuntime implements RuntimeExtensionInterface
{
    private array $cache = [];

    /**
     * CoreRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * To get Symfony version.
     */
    public function symfonyVersion(): string
    {
        $version = Kernel::VERSION;
        $matches = explode('.', $version);
        $minVersion = strlen('.'.end($matches));

        return substr($version, 0, -$minVersion);
    }

    /**
     * To get PHP version.
     */
    public function phpversion(): string
    {
        $version = phpversion();
        $matches = explode('.', $version);
        $minVersion = strlen('.'.end($matches));

        return substr($version, 0, -$minVersion);
    }

    /**
     * Get BlockTypes & Actions groups.
     */
    public function blockTypesActionsGroups(LayoutConfiguration $configuration, object $entity): array
    {
        $groups = [];
        $groups = $this->getBlockTypes($configuration, $entity, $groups);

        return $this->getModules($configuration, $groups);
    }

    /**
     * To check if entity is allowed to show in index for current User.
     *
     * @throws InvalidArgumentException
     */
    public function indexAllowed(mixed $entity, bool $isInternal): bool
    {
        if ($entity instanceof Group) {
            $isInternalGroup = false;
            foreach ($entity->getRoles() as $role) {
                if ('ROLE_INTERNAL' === $role->getName()) {
                    $isInternalGroup = true;
                    break;
                }
            }
            if ($isInternalGroup && !$isInternal) {
                return false;
            }
        } elseif ($entity instanceof User) {
            $isInternalUser = false;
            foreach ($entity->getRoles() as $role) {
                if ('ROLE_INTERNAL' === $role) {
                    $isInternalUser = true;
                    break;
                }
            }
            if ($isInternalUser && !$isInternal) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get season icon.
     *
     * @throws \Exception
     */
    public function seasonIcon(): ?string
    {
        $currentDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $year = $currentDate->format('Y');
        if ($currentDate >= new \DateTime($year.'-09-22 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-12-21 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin umbrella-color';
        }
        if ($currentDate >= new \DateTime($year.'-12-22 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime((intval($year) + 1).'-03-19 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin tree-color';
        }
        if ($currentDate >= new \DateTime((intval($year) - 1).'-12-22 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-03-19 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin tree-color';
        }
        if ($currentDate >= new \DateTime($year.'-03-20 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-06-19 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin spring-color';
        }
        if ($currentDate >= new \DateTime($year.'-06-20 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-09-21 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin sun-color';
        }

        return 'fad globe';
    }

    /**
     * Get log alert.
     */
    public function logAlert(): bool
    {
        $lastLog = $this->coreLocator->em()->getRepository(Log::class)->findUnread();

        return !empty($lastLog);
    }

    /**
     * Init entity property if not existing.
     */
    public function methodInit(mixed $entity, string $property): string
    {
        $getter = 'get'.ucfirst($property);

        return method_exists($entity, $getter) ? $property : 'id';
    }

    /**
     * Check button display status.
     */
    public function buttonChecker(string $route, mixed $entity, array $interface = []): mixed
    {
        if (isset($interface['buttonsChecker'][$route])) {
            $properties = explode('.', $interface['buttonsChecker'][$route]);
            $display = true;
            foreach ($properties as $property) {
                $getter = 'get'.ucfirst($property);
                if (is_object($entity) && method_exists($entity, $getter)) {
                    $display = $entity->$getter();
                    $entity = $entity->$getter();
                }
            }

            return $display;
        }

        return true;
    }

    /**
     * Check button display by User role.
     */
    public function buttonRoleChecker(string $route, array $interface = []): bool
    {
        if (isset($interface['rolesChecker'][$route])) {
            return $this->coreLocator->authorizationChecker()->isGranted($interface['rolesChecker'][$route]);
        }

        return true;
    }

    /**
     * Get Block Feature name.
     */
    public function blockFeatureName(?int $featureId = null): ?string
    {
        $feature = $featureId ? $this->coreLocator->em()->getRepository(Feature::class)->find($featureId) : false;
        return $feature ? $feature->getAdminName() : null;
    }

    /**
     * Get BlockTypes groups.
     */
    private function getBlockTypes(LayoutConfiguration $configuration, object $entity, array $groups = []): array
    {
        $layoutGroups = ['layout', 'form'];
        $done = [];

        foreach ($configuration->getBlockTypes() as $blockType) {
            $groupCategory = $blockType->getCategory();
            $groupCategory = str_contains($groupCategory, 'layout-') ? 'layout' : $groupCategory;
            foreach ($layoutGroups as $group) {
                if ($group === $groupCategory && 'action' !== $blockType->getSlug()) {
                    $groups['block'][$group][$blockType->getPosition()] = $blockType;
                    $done[] = $blockType->getId();
                    ksort($groups['block'][$group]);
                }
            }
            if (!in_array($blockType->getId(), $done) && 'action' !== $blockType->getSlug()) {
                $groupName = $blockType->isDropdown() ? 'secondary' : 'main';
                $groups['block'][$groupName][$blockType->getPosition()] = $blockType;
                ksort($groups['block'][$groupName]);
            }
        }

        if (!empty($groups['block'])) {
            ksort($groups['block']);
        }

        $asCustomLayout = method_exists($entity, 'isCustomLayout') && $entity->isCustomLayout();
        if ($asCustomLayout && method_exists($entity, 'getPublicationStart')) {
            $blockType = $this->coreLocator->em()->getRepository(BlockType::class)->findOneBy(['slug' => 'layout-dates']);
            if ($blockType instanceof BlockType) {
                $groups['block']['layout'][$blockType->getPosition()] = $blockType;
            }
            $blockType = $this->coreLocator->em()->getRepository(BlockType::class)->findOneBy(['slug' => 'layout-back-button']);
            if ($blockType instanceof BlockType) {
                $groups['block']['layout'][$blockType->getPosition()] = $blockType;
            }
        }

        return $groups;
    }

    /**
     * Get Modules groups.
     */
    private function getModules(LayoutConfiguration $configuration, array $groups = []): array
    {
        foreach ($configuration->getModules() as $module) {
            foreach ($module->getActions() as $action) {
                if ($action->isActive()) {
                    $groupName = $action->isDropdown() ? 'secondary' : 'main';
                    $groups['module'][$groupName][$action->getPosition()] = $action;
                    ksort($groups['module'][$groupName]);
                }
            }
        }
        if (isset($groups['module'])) {
            ksort($groups['module']);
        }

        return $groups;
    }

    /**
     * Check if BlockTYpe is active.
     */
    public function blockTypesActive(string $slug, ?string $classname = null): bool
    {
        $this->cache['activeBlockTypes'] = !empty($this->cache['activeBlockTypes']) ? $this->cache['activeBlockTypes'] : [];
        $this->cache['layoutConfiguration'] = !empty($this->cache['layoutConfiguration']) ? $this->cache['layoutConfiguration']
            : $this->coreLocator->em()->getRepository(LayoutConfiguration::class)->findOneBy(['website' => $this->coreLocator->website()->entity, 'entity' => $classname ?: Page::class]);

        if (empty($this->cache['activeBlockTypes'])) {
            foreach ($this->cache['layoutConfiguration']->getBlockTypes() as $blockType) {
                $this->cache['activeBlockTypes'][] = $blockType->getSlug();
            }
        }

        return in_array($slug, $this->cache['activeBlockTypes']);
    }
}
