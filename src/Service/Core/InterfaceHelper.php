<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Entity;
use App\Entity\Core\Website;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * InterfaceHelper.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => InterfaceHelper::class, 'key' => 'interface_helper'],
])]
class InterfaceHelper
{
    private ?Request $request;
    private mixed $website = null;
    private array $interface = [];
    private ?string $masterField = null;
    private ?string $parentMasterField = null;
    private array $labels = [];
    private array $cache = [];
    private mixed $entity = null;
    private ?string $name = null;
    private static array $interfacesCache = [];

    /**
     * InterfaceHelper constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->setRequest($requestStack);
        $this->setWebsite();
    }

    /**
     * To set Request.
     */
    private function setRequest(?RequestStack $request): void
    {
        $this->request = $request ? $this->requestStack->getMainRequest() : null;
    }

    /**
     * To set Website.
     */
    private function setWebsite(): void
    {
        $this->website = $this->coreLocator->website();
    }

    /**
     * Generate Interface.
     *
     * @throws NonUniqueResultException
     */
    public function generate(?string $classname = null, array $options = []): bool|array
    {
        if (!$classname) {
            return false;
        }
        $this->setInterface($classname, $options);

        return $this->getInterface();
    }

    /**
     * Get Interface.
     */
    public function getInterface(): array
    {
        return $this->interface;
    }

    /**
     * Set Interface.
     *
     * @throws NonUniqueResultException
     */
    public function setInterface(string $classname, array $options = []): void
    {
        $classname = str_replace('Proxies\__CG__\\', '', $classname);
        $cacheKey = $classname . serialize($options);
        if (isset(self::$interfacesCache[$cacheKey])) {
            $cache = self::$interfacesCache[$cacheKey];
            $this->interface = $cache['interface'];
            $this->masterField = $cache['masterField'];
            $this->parentMasterField = $cache['parentMasterField'];
            $this->labels = $cache['labels'];
            $this->entity = $cache['entity'];
            return;
        }

        $matchesEntity = explode('\\', $classname);
        $referClass = class_exists($classname) ? new $classname() : null;
        $this->interface = is_object($referClass) && method_exists($referClass, 'getInterface') && !empty($referClass::getInterface())
            ? $referClass::getInterface()
            : [];
        $actionCode = str_contains($classname, 'Module') && !empty($matchesEntity[count($matchesEntity) - 2])
            ? strtolower($matchesEntity[count($matchesEntity) - 2])
            : null;

        $inAdmin = $this->request instanceof Request && preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->request->getUri());
        $interfaceName = !empty($this->interface['name']) ? $this->interface['name'] : null;
        $this->interface['name'] = 'website' === $interfaceName ? 'site' : $interfaceName;

        $this->setEntity($classname);

        $website = $this->request && $this->request->get('website')
            ? $this->entityManager->getRepository(Website::class)->find($this->request->get('website'))
            : null;

        $entity = $this->getEntity();
        $this->setMasterField($entity, $classname);
        $this->setParentMasterField($entity);
        $this->setLabels();

        $this->interface['masterField'] = $this->masterField;
        $this->interface['masterFieldId'] = $this->request && $this->masterField ? $this->request->get($this->masterField) : null;
        $this->interface['parentMasterField'] = $this->parentMasterField;
        $this->interface['parentMasterFieldId'] = $this->request && $this->parentMasterField ? $this->request->get($this->parentMasterField) : null;
        $this->interface['website'] = $website;
        $this->interface['entityCode'] = strtolower(end($matchesEntity));
        $this->interface['actionCode'] = $actionCode;
        $this->interface['classname'] = $classname;
        $this->interface['configuration'] = $inAdmin ? $this->getConfiguration($classname, $options) : [];
        $this->interface['labels'] = $this->labels;
        $this->interface['entity'] = $this->getEntity();
        $this->interface['prePersistTitle'] = $this->interface['prePersistTitle'] ?? true;

        if (isset($options['interfaceHideColumns']) && is_array($options['interfaceHideColumns'])) {
            $hideColumns = !empty($this->interface['hideColumns']) ? $this->interface['hideColumns'] : [];
            $hideColumns = array_unique(array_merge($options['interfaceHideColumns'], $hideColumns));
            $this->interface['hideColumns'] = $hideColumns;
        }

        $fields = ['disabledImport', 'disabledExport', 'disabledLayout', 'disabledPosition', 'disabledUrl'];
        foreach ($fields as $field) {
            if (!isset($this->interface[$field])) {
                $this->interface[$field] = false;
            }
        }

        self::$interfacesCache[$cacheKey] = [
            'interface' => $this->interface,
            'masterField' => $this->masterField,
            'parentMasterField' => $this->parentMasterField,
            'labels' => $this->labels,
            'entity' => $this->entity,
        ];
    }

    /**
     * Get interface by name.
     *
     * @throws NonUniqueResultException
     */
    public function interfaceByName(string $name): array
    {
        $metasData = !empty($this->cache['metaData']) ? $this->cache['metaData'] : [];
        if (!$metasData) {
            $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
            foreach ($metasData as $metaData) {
                if ( 0 === $metaData->getReflectionClass()->getModifiers()) {
                    $interface = $this->generate($metaData->getName());
                    if (!empty($interface['name'])) {
                        $this->cache['metaData'][$interface['name']] = $interface;
                    }
                }
            }
        }

        return !empty($this->cache['metaData'][$name]) ? $this->cache['metaData'][$name] : [];
    }

    /**
     * Get MasterField.
     */
    public function getMasterField(): ?string
    {
        return $this->masterField;
    }

    /**
     * Set MasterField.
     */
    public function setMasterField(mixed $entity = null, mixed $classname = null): void
    {
        if (is_object($entity) && method_exists($entity, 'getMasterField')) {
            $reflectionMethod = new \ReflectionMethod($entity, 'getMasterField');
            $this->masterField = $reflectionMethod->isStatic() && !empty($entity::getMasterField()) ? $this->getEntity()::getMasterField() : null;
        } else {
            $this->masterField = null;
        }

        if (!$this->masterField && $classname && str_contains($classname, 'MediaRelation') && str_contains($classname, 'Layout')) {
            $matchesEntity = explode('\\', $classname);
            $this->masterField = strtolower(str_replace('MediaRelation', '', end($matchesEntity)));
        }
    }

    /**
     * Set MasterField.
     */
    public function setParentMasterField(mixed $entity = null): void
    {
        if (is_object($entity) && method_exists($entity, 'getParentMasterField')) {
            $reflectionMethod = new \ReflectionMethod($entity, 'getParentMasterField');
            $this->parentMasterField = $reflectionMethod->isStatic() && !empty($entity::getParentMasterField()) ? $this->getEntity()::getParentMasterField() : null;
        } else {
            $this->parentMasterField = null;
        }
    }

    /**
     * Get Labels.
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Set Labels.
     */
    public function setLabels(): void
    {
        $entity = $this->getEntity();
        if (is_object($entity) && method_exists($entity, 'getLabels')) {
            $reflectionMethod = new \ReflectionMethod($entity, 'getLabels');
            $this->labels = $reflectionMethod->isStatic() && !empty($entity::getLabels()) ? $this->getEntity()::getLabels() : [];
        } else {
            $this->parentMasterField = null;
        }
    }

    /**
     * Get Entity.
     */
    public function getEntity(): mixed
    {
        if ($this->entity instanceof Website) {
            $configuration = $this->entity->getConfiguration();
            if ($configuration) {
                $this->entityManager->refresh($configuration);
            }
        }

        return $this->entity;
    }

    /**
     * Set Entity.
     *
     * @throws NonUniqueResultException
     */
    public function setEntity(string $classname): void
    {
        if ($this->request instanceof Request) {
            if (!empty($this->interface['name']) && !empty($this->request->get($this->interface['name'])) && 'configuration' !== $this->interface['name']) {
                if (is_numeric($this->request->get($this->interface['name']))) {
                    $referClass = new $classname();
                    if (method_exists($referClass, 'getLayout')) {
                        $this->entity = $this->entityManager->createQueryBuilder()->select('e')
                            ->from($classname, 'e')
                            ->leftJoin('e.layout', 'l')
                            ->leftJoin('l.zones', 'z')
                            ->leftJoin('z.cols', 'c')
                            ->leftJoin('c.blocks', 'b')
                            ->andWhere('e.id = :id')
                            ->setParameter('id', $this->request->get($this->interface['name']))
                            ->addSelect('l')
                            ->addSelect('z')
                            ->addSelect('c')
                            ->addSelect('b')
                            ->getQuery()
                            ->getOneOrNullResult();
                    } else {
                        $this->entity = $this->entityManager->getRepository($classname)->find($this->request->get($this->interface['name']));
                    }
                }
            } elseif ('configuration' !== $this->interface['name'] && $this->interface['name'] && !$this->request->get($this->interface['name'])) {
                $this->entity = class_exists($classname) ? new $classname() : null;
            }
            if (!$this->entity && $this->interface['name'] && is_array($this->request->get($this->interface['name']))) {
                $this->entity = class_exists($classname) ? new $classname() : null;
            }
        }

        if (empty($this->entity)) {
            $this->entity = class_exists($classname) ? new $classname() : null;
        }
    }

    /**
     * Get Name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set Name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get Entity ConfigurationModel.
     */
    public function getConfiguration(string $classname, array $options = []): object
    {
        $configuration = [];
        $entity = $this->cache['entityConf'][$classname] = !empty($this->cache['entityConf'][$classname])
            ? $this->cache['entityConf'][$classname] : $this->entityManager->getRepository(Entity::class)->optimizedQuery($classname, $this->website);
        $properties = $this->entityManager->getClassMetadata(Entity::class)->getReflectionProperties();
        $default = new Entity();
        foreach ($properties as $property => $reflexionProperty) {
            $getMethod = 'get'.ucfirst($property);
            if ($entity && method_exists($entity, $getMethod) || $default && method_exists($default, $getMethod)) {
                $configuration[$property] = !$entity ? $default->$getMethod() : $entity->$getMethod();
            }
            $isMethod = 'is'.ucfirst($property);
            if ($entity && method_exists($entity, $isMethod) || $default && method_exists($default, $isMethod)) {
                $configuration[$property] = !$entity ? $default->$isMethod() : $entity->$isMethod();
            }
        }
        if (isset($options['interfaceOrderBy'])) {
            $configuration['orderBy'] = $options['interfaceOrderBy'];
        }
        if (isset($options['interfaceOrderSort'])) {
            $configuration['orderSort'] = $options['interfaceOrderSort'];
        }

        return (object) $configuration;
    }
}
