<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Yaml\Yaml;

/**
 * EntityService.
 *
 * Manage Entity configuration
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class EntityService
{
    private ?User $createdBy = null;
    private ?int $position;
    private Website $website;

    /**
     * EntityService constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Execute.
     */
    public function execute(Website $website, string $locale): void
    {
        $this->position = count($this->entityManager->getRepository(Entity::class)->findAll()) + 1;
        $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $configs = $this->getCoreValues();
        foreach ($metasData as $metadata) {
            $matches = explode(DIRECTORY_SEPARATOR, $metadata->getName());
            $entityName = end($matches);
            if (!str_contains($entityName, 'Base') && !str_contains($entityName, 'Intl')) {
                $this->entity($website, $metadata, $locale, $configs);
            }
        }
        $this->entityManager->flush();
    }

    /**
     * Get core values.
     */
    private function getCoreValues(): array
    {
        $values = [];
        $coreDirname = $this->projectDir.'/bin/data/fixtures/';
        $coreDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $coreDirname);
        $imports = Yaml::parseFile($coreDirname.'entity-configuration.yaml')['imports'];
        foreach ($imports as $import) {
            $values = array_merge($values, Yaml::parseFile($coreDirname.$import['resource']));
        }

        return $values;
    }

    /**
     * Set Entity.
     */
    private function entity(Website $website, ClassMetadata $metadata, string $locale, array $configs): void
    {
        $classname = $metadata->getName();
        $config = !empty($configs[$classname]) ? $configs[$classname] : [];
        $referClass = new $classname();

        /** @var Entity $existing */
        $existing = $this->entityManager->getRepository(Entity::class)->findOneBy([
            'website' => $website,
            'className' => $metadata->getName(),
        ]);

        $entity = $existing ?: new Entity();
        $entity->setAdminName($this->getAdminName($metadata, $config, $locale));

        if (!$existing) {
            $entity->setWebsite($website);
            $entity->setClassName($metadata->getName());
            $entity->setColumns($this->getColumns($entity, $config));
            $entity->setSearchFields($this->getSearchFields($entity, $config));
            $entity->setSearchFilters($this->getSearchFilters($entity, $config));
            $entity->setOrderBy($this->getOrderBy($entity, $config, $referClass));
            $entity->setOrderSort($this->getOrderSort($entity, $config));
            $entity->setShowView($this->getShowView($entity, $config));
            $entity->setExports($this->getExports($entity, $config));
            $entity->setUniqueLocale($this->isUniqueLocale($entity, $config));
            $entity->setMediaMulti($this->isMediaMulti($entity, $config));
            $entity->setCard($this->isCard($entity, $config));
            $entity->setPosition($this->position);
            $entity->setAdminLimit($this->getAdminLimit($entity, $config));

            $session = new Session();
            $sessionSlug = str_replace('\\', '_', $entity->getClassName());
            $session->remove('configuration_'.$sessionSlug);

            if ($this->createdBy) {
                $entity->setCreatedBy($this->createdBy);
            }
        }

        $this->entityManager->persist($entity);

        ++$this->position;
    }

    /**
     * Get AdminName.
     */
    public function getAdminName(ClassMetadata $metadata, array $config, string $locale): ?string
    {
        $adminName = !empty($config['translations']['singular'][$locale]) ? $config['translations']['singular'][$locale] : $metadata->getName();

        return ltrim($adminName, '__');
    }

    /**
     * Get columns.
     */
    public function getColumns(Entity $entity, array $config): array
    {
        $columns = !empty($config['columns']) ? $config['columns'] : [];
        if (!$columns) {
            $columns = $entity->getColumns();
        }

        return $columns;
    }

    /**
     * Get search fields.
     */
    public function getSearchFields(Entity $entity, array $config): array
    {
        $columns = !empty($config['searchFields']) ? $config['searchFields'] : (!empty($config['columns']) ? $config['columns'] : []);
        if (!$columns) {
            $columns = $entity->getSearchFields();
        }

        return $columns;
    }

    /**
     * Get search filter fields.
     */
    public function getSearchFilters(Entity $entity, array $config): array
    {
        $columns = !empty($config['searchFilters']) ? $config['searchFilters'] : [];
        if (!$columns) {
            $columns = $entity->getSearchFilters();
        }

        return $columns;
    }

    /**
     * Get orderBy.
     */
    public function getOrderBy(Entity $entity, array $config, mixed $referClass): ?string
    {
        $order = !empty($config['orderBy']) ? $config['orderBy'] : $entity->getOrderBy();
        $setter = 'set'.ucfirst($order);
        if ('id' !== $order && !method_exists($referClass, $setter)) {
            $order = 'id';
        }

        return $order;
    }

    /**
     * Get orderSort.
     */
    public function getOrderSort(Entity $entity, array $config): ?string
    {
        return !empty($config['orderSort']) ? $config['orderSort'] : $entity->getOrderSort();
    }

    /**
     * Get columns.
     */
    public function getShowView(Entity $entity, array $config): array
    {
        $show = !empty($config['show']) ? $config['show'] : [];
        if (empty($show)) {
            $show = $entity->getShowView();
        }

        return $show;
    }

    /**
     * Get exports.
     */
    public function getExports(Entity $entity, array $config): array
    {
        return !empty($config['exports']) ? $config['exports'] : $entity->getExports();
    }

    /**
     * Get unique Locale.
     */
    public function isUniqueLocale(Entity $entity, array $config): bool
    {
        return $config['uniqueLocale'] ?? $entity->isUniqueLocale();
    }

    /**
     * Get media multi.
     */
    public function isMediaMulti(Entity $entity, array $config): bool
    {
        return $config['mediaMulti'] ?? $entity->isMediaMulti();
    }

    /**
     * Get is seo card.
     */
    public function isCard(Entity $entity, array $config): bool
    {
        return $config['card'] ?? $entity->isCard();
    }

    /**
     * Get admin limit.
     */
    public function getAdminLimit(Entity $entity, array $config): ?int
    {
        return $config['adminLimit'] ?? $entity->getAdminLimit();
    }

    /**
     * Set WebsiteModel.
     */
    public function website(Website $website): void
    {
        $this->website = $website;
    }

    /**
     * Set WebsiteModel.
     */
    public function getWebsite(): Website
    {
        return $this->website;
    }

    /**
     * Set CreatedBy.
     */
    public function createdBy(?User $createdBy = null): void
    {
        $this->createdBy = $createdBy;
    }
}
