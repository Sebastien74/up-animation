<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Api\Api;
use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Security\User;
use App\Entity\Seo\SeoConfiguration;
use App\Entity\Translation;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * DoctrineEventsListener.
 *
 * Listen Doctrine events
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::onFlush, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postRemove, priority: 500, connection: 'default')]
// #[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
// #[AsDoctrineListener(event: Events::postLoad, priority: 500, connection: 'default')]
// #[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
// #[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
// #[AsDoctrineListener(event: Events::postFlush, priority: 500, connection: 'default')]
class DoctrineEventsListener
{
    private const bool LOG_EVENTS = false;
    private const array DISABLED_ENTITIES = [
        Translation\Translation::class,
        Translation\TranslationUnit::class,
        Translation\TranslationDomain::class,
    ];
    private const array ALLOW_URIS = [
        '/switch-boolean/',
        '/layouts/zones/cols/size',
        '/layouts/zones/cols/blocks/size',
        '/layouts/zones/cols/standardize-elements',
        '/layouts/zones/standardize-elements',
        '/seo/urls/status',
        '/menus/edit/',
    ];

    private ?Request $request;
    private bool $inAdmin;
    private array $entities = [];
    private array $objects = [];

    /**
     * DoctrineEventsListener constructor.
     */
    public function __construct(
        private readonly AdminLocatorInterface $adminLocator,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->setRequest();
    }

    /**
     * To set Request.
     */
    private function setRequest(): void
    {
        $this->request = $this->coreLocator->request();
        $this->inAdmin = $this->request instanceof Request && str_contains($this->request->getUri(), '/admin-'.$_ENV['SECURITY_TOKEN'].'/');
    }

    //    public function postLoad(Event\PostLoadEventArgs $args): void {}
    //    public function prePersist(Event\PrePersistEventArgs $args): void{}
    //    public function postPersist(Event\PostPersistEventArgs $args): void{}
    //    public function postUpdate(Event\PostUpdateEventArgs $args): void{}
    //    public function postFlush(Event\PostFlushEventArgs $args): void{}

    /**
     * preUpdate.
     *
     * @throws InvalidArgumentException|MappingException|NonUniqueResultException|ReflectionException
     */
    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if ($this->request instanceof Request && $this->processAllowed()) {
            $entity = $args->getObject();
            $this->entities[] = $entity;
            $website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost();
            $this->setWebsite($entity);
            $this->process($args, $entity, 'postUpdate');
            if ($this->inAdmin) {
                $this->adminLocator->titleService()->execute($website, $entity);
            }
            $this->setMasterField($entity);
        }
    }

    /**
     * onFlush.
     *
     * @throws ContainerExceptionInterface|NonUniqueResultException|NotFoundExceptionInterface|InvalidArgumentException|ReflectionException
     */
    public function onFlush(Event\OnFlushEventArgs $args): void
    {
        if ($this->inAdmin && $this->request instanceof Request && $this->processAllowed()) {
            $unitOfWork = $args->getObjectManager()->getUnitOfWork();
            $entities = $unitOfWork->getScheduledEntityUpdates();
            $entities = !$entities ? $unitOfWork->getScheduledEntityInsertions() : $entities;
            $entities = !$entities ? $this->entities : $entities;
            foreach ($entities as $entity) {
                $classname = str_replace('Proxies\__CG__\\', '', get_class($entity));
                if ($entity->getId() && empty($this->objects[$classname])) {
                    $this->logger($entity, 'onFlush');
                    $this->setMasterField($entity, true);
                    if ($this->inAdmin) {
                        $this->coreLocator->cacheService()->clearCaches($entity);
                    }
                    $this->objects[$classname][$entity->getId()] = $entity;
                }
                $this->removeCacheFiles($entity);
            }
        }
    }

    /**
     * postRemove.
     */
    public function postRemove(Event\PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $allowed = $this->inAdmin && $this->request instanceof Request;
        if ($allowed && $this->inAdmin && !$this->disabledCache($entity) || $this->request->isMethod('delete')) {
            $this->coreLocator->cacheService()->clearCaches($entity);
            $this->logger($entity, 'postRemove');
        }
    }

    /**
     * Process.
     */
    private function process(mixed $args, mixed $entity, string $type): void
    {
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        if ($user instanceof User && 'postUpdate' === $type && method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($user);
        } elseif ($user instanceof User && 'persist' === $type && method_exists($entity, 'setCreatedBy') && !$entity->getCreatedBy()) {
            $entity->setCreatedBy($user);
        }
    }

    /**
     * To set entity WebsiteModel relation.
     *
     * @throws MappingException
     */
    private function setWebsite(mixed $entity): void
    {
        if (method_exists($entity, 'getWebsite') && !$entity->getWebsite() instanceof Website) {
            $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
            $mapping = $metadata->getAssociationMapping('website');
            $asMultiple = false;
            if (!empty($mapping['joinColumns'])) {
                foreach ($mapping['joinColumns'] as $joinColumn) {
                    if (isset($joinColumn['name']) && str_contains($joinColumn['name'], 'website') && isset($joinColumn['referencedColumnName']) && 'id' === $joinColumn['referencedColumnName'] && isset($joinColumn['unique'])) {
                        $asMultiple = !$joinColumn['unique'];
                        break;
                    }
                }
            }
            if ($asMultiple) {
                $website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost();
                $entity->setWebsite($website);
            }
        }
    }

    /**
     * To set master field entity.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|ReflectionException|Exception
     */
    private function setMasterField(mixed $entity, bool $clearCache = false): void
    {
        $masterField = null;
        if (is_object($entity) && method_exists($entity, 'getMasterField')) {
            $reflectionMethod = new \ReflectionMethod($entity, 'getMasterField');
            $masterField = $reflectionMethod->isStatic() && !empty($entity::getMasterField()) ? $entity::getMasterField() : null;
        }

        if ($masterField && 'website' !== $masterField) {
            $method = 'get'.ucfirst($masterField);
            $masterEntity = method_exists($entity, $method) ? $entity->$method() : null;
            if (is_object($masterEntity) && method_exists($masterEntity, 'setUpdatedAt')) {
                $masterEntity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($masterEntity);
                if ($this->inAdmin && $clearCache) {
                    $this->coreLocator->cacheService()->clearCaches($entity);
                }
            }
        }
    }

    /**
     * Check if is Listener request.
     */
    private function disabledCache(mixed $entity): bool
    {
        $appListenerDirname = $this->coreLocator->projectDir().'\\src\\EventListener';
        $appSubscriberDirname = $this->coreLocator->projectDir().'\\src\\EventSubscriber';
        if (is_object($entity) && !$entity->getId()) {
            return true;
        }
        if (is_object($entity) && in_array(get_class($entity), self::DISABLED_ENTITIES) && empty($_POST)) {
            return true;
        }
        foreach (debug_backtrace() as $backtrace) {
            $file = is_array($backtrace) && !empty($backtrace['file']) ? $backtrace['file'] : null;
            $doctrineEvent = $file && str_contains($file, 'DoctrineEventsListener');
            $appListener = $file && preg_match('/'.$this->formatDirname($appListenerDirname).'/', $this->formatDirname($file));
            $appSubscriber = $file && preg_match('/'.$this->formatDirname($appSubscriberDirname).'/', $this->formatDirname($file));
            if (!$doctrineEvent && $appListener || !$doctrineEvent && $appSubscriber) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format dirname.
     */
    private function formatDirname(?string $dirname = null): ?string
    {
        return $dirname ? str_replace(['\\', '/'], '-', $dirname) : null;
    }

    /**
     * To check if process allowed.
     */
    private function processAllowed(): bool
    {
        if ($this->request->isMethod('post')) {
            return true;
        }

        return $this->forceUriCache();
    }

    /**
     * To check if process allowed.
     */
    private function forceUriCache(): bool
    {
        foreach (self::ALLOW_URIS as $uri) {
            if (str_contains($this->request->getUri(), $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * To remove cache files.
     */
    private function removeCacheFiles(mixed $entity): void
    {
        $filesystem = new Filesystem();
        $entityClassname = str_replace('Proxies\__CG__\\', '', get_class($entity));
        $entitiesCache = [
            Api::class => ['apimodel'],
            SeoConfiguration::class => ['apimodel'],
            Website::class => ['apimodel', 'domains'],
            Configuration::class => ['pages'],
            Information::class => ['apimodel'],
        ];

        foreach ($entitiesCache as $classname => $filenames) {
            if ($entityClassname === $classname) {
                foreach ($filenames as $filename) {
                    $finder = Finder::create();
                    $finder->files()->in($this->coreLocator->cacheDir())->name('*'.$filename.'*');
                    foreach ($finder as $file) {
                        $filesystem->remove($file->getRealPath());
                    }
                }
            }
        }
    }

    /**
     * To log event.
     */
    private function logger(mixed $entity, string $event): void
    {
        if (self::LOG_EVENTS) {
            $logger = new Logger($event);
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/doctrine-listener.log', 10, Level::Info));
            $classname = str_replace('Proxies\__CG__\\', '', get_class($entity));
            $logger->info('[classname] : '.$classname);
        }
    }
}
