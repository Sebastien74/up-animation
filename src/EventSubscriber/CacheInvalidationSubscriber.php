<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\BaseInterface;
use App\Entity\BaseMediaRelation;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Zone;
use App\Entity\Layout\Col;
use App\Entity\Layout\Block;
use App\Entity\Layout\PageIntl;
use App\Entity\Layout\ZoneIntl;
use App\Entity\Layout\BlockIntl;
use App\Entity\Module\Table\Table;
use App\Entity\Module\Table\TableIntl;
use App\Entity\Module\Table\Col as TableCol;
use App\Entity\Module\Table\ColIntl as TableColIntl;
use App\Entity\Module\Table\Cell;
use App\Entity\Module\Table\CellIntl;
use App\Entity\Media\MediaRelationIntl;
use App\Entity\Seo\Url;
use App\Service\Interface\CoreLocatorInterface;
use DateMalformedStringException;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Mapping\MappingException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[AsDoctrineListener(event: Events::onFlush)]
class CacheInvalidationSubscriber
{
    private \Symfony\Component\PropertyAccess\PropertyAccessor $propertyAccessor;
    private array $pages = [];
    private array $websites = [];
    private array $itemsToDelete = [];

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * onFlush.
     *
     * @throws DateMalformedStringException|MappingException|InvalidArgumentException
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->coreLocator->inAdmin()) {

            $website = $this->coreLocator->website();
            $em = $args->getObjectManager();
            if (!$cache = $em->getConfiguration()->getResultCache()) return;
            $uow = $em->getUnitOfWork();
            $entities = array_merge($uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates(), $uow->getScheduledEntityDeletions());
            $websitesToInvalidate = [];
            $pagesToInvalidate = [];
            $this->pages = [];
            $this->websites = [];
            $this->itemsToDelete = [];

            $namespace = $this->coreLocator->request()->query->get('entityNamespace');
            $entityId = $this->coreLocator->request()->query->get('referEntityId');
            if ($namespace && $entityId) {
                $inPage = [];
                $namespace = urldecode($namespace);
                foreach ($website->configuration->allLocales as $locale) {
                    $pages = $this->coreLocator->em()->getRepository(Page::class)->findAllByAction($website->entity, $locale, $namespace, [$entityId]);
                    foreach ($pages as $page) {
                        if ($page && !array_key_exists($page->getId(), $inPage)) $entities[] = $inPage[$page->getId()] = $page;
                    }
                }
                $this->invalidateActionResultCache($namespace, $entityId, $website, $em);
            }

            foreach ($entities as $entity) {
                if ($entity instanceof Page) {
                    $pagesToInvalidate[$entity->getId()] = $entity;
                } elseif ($entity instanceof BaseInterface) {
                    if ($page = $this->findPage($entity, $em)) {
                        $pagesToInvalidate[$page->getId()] = $page;
                    } elseif ($w = $this->findWebsite($entity, $em)) {
                        $websitesToInvalidate[$w->getId()] = $w;
                    }
                }
                if ($entity instanceof BaseMediaRelation || $entity instanceof MediaRelationIntl) $this->invalidateMediasCache($entity, $em);
            }

            foreach ($pagesToInvalidate as $page) {
                if ($page->getId() && $em->contains($page)) {
                    $page->setUpdatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                    $this->coreLocator->em()->persist($page);
                    $uow->scheduleForUpdate($page);
                    $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Page::class), $page);
                }
                if ($page->getId()) {
                    $this->invalidatePageResultCache($page, $website, $em);
                }
            }

            foreach ($websitesToInvalidate as $w) {
                $w->setCacheClearDate(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                $this->coreLocator->em()->persist($w);
                $uow->scheduleForUpdate($w);
                $uow->recomputeSingleEntityChangeSet($em->getClassMetadata(Website::class), $w);
                $this->itemsToDelete[] = 'website-id-'.$w->getId();
                foreach (['website-default', 'websites-actives', 'websites-switcher', 'websites-all'] as $key) {
                    $this->itemsToDelete[] = $key;
                }
                foreach ($w->getConfiguration()->getDomains() as $domain) {
                    $host = $domain->getName() ? str_replace(['https://', 'http://'], '', $domain->getName()) : null;
                    $this->itemsToDelete[] = 'website-'.md5($host);
                }
                foreach ($w->getConfiguration()->getAllLocales() as $locale) {
                    $this->itemsToDelete[] = 'config-admin-'.$w->getId().'-'.$locale;
                }
            }

            if ($this->itemsToDelete) {
                $cache->deleteItems(array_unique($this->itemsToDelete));
            }
        }
    }

    /**
     * Invalidate Result Cache for Action.
     *
     * @throws InvalidArgumentException
     */
    private function invalidateActionResultCache(string $namespace, mixed $entityId, object $website, EntityManagerInterface $em): void
    {
        if (!$em->getConfiguration()->getResultCache()) return;

        foreach ($website->configuration->allLocales as $locale) {
            $idsStr = is_array($entityId) ? implode('_', $entityId) : (string) $entityId;
            $wId = $website->id;
            $this->itemsToDelete[] = 'pages_action_'.md5($namespace.'_'.$idsStr.'_'.$locale.'_'.$wId);
            $this->itemsToDelete[] = 'page_action_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$entityId);
            $this->itemsToDelete[] = 'pages_action_ids_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$idsStr);
            $this->itemsToDelete[] = 'page_action_slug_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$entityId);
            $this->itemsToDelete[] = 'pages_action_slug_'.md5($wId.'_'.$locale.'_'.$namespace.'_'.$entityId);
        }
    }

    /**
     * Invalidate Result Cache for Page.
     *
     * @throws InvalidArgumentException
     */
    private function invalidatePageResultCache(Page $page, object $website, EntityManagerInterface $em): void
    {
        if (!$em->getConfiguration()->getResultCache()) return;

        foreach ($page->getUrls() as $url) {
            $locale = $url->getLocale();
            $urlCode = $url->getCode();
            if ($page->isAsIndex()) {
                $this->itemsToDelete[] = 'page-index-'.$website->id.'-'.$locale;
            } elseif ($urlCode) {
                $this->itemsToDelete[] = 'page-'.$website->id.'-'.$urlCode.'-'.$locale;
            }
            $this->itemsToDelete[] = 'page-url-'.md5($page->getId().'_'.$locale);
            $this->itemsToDelete[] = 'page_url_id_'.$url->getId().'_'.$locale;
            $this->itemsToDelete[] = 'pages_index_url_'.md5($page->getId().'_'.$locale);
        }
        if ($page->getLayout()) $this->itemsToDelete[] = 'layout_'.$page->getLayout()->getId();
    }

    /**
     * Invalidate Medias Cache.
     *
     * @throws InvalidArgumentException
     */
    private function invalidateMediasCache(BaseMediaRelation|MediaRelationIntl $entity, EntityManagerInterface $em): void
    {
        if (!$em->getConfiguration()->getResultCache()) return;

        $class = $em->getClassMetadata(get_class($entity))->getName();
        $masterField = ($entity instanceof MediaRelationIntl) ? 'mediaRelation' : (method_exists($class, 'getMasterField') ? $class::getMasterField() : null);

        if (!$masterField) {
            foreach ($em->getClassMetadata($class)->getAssociationMappings() as $fieldName => $mapping) {
                if (property_exists($mapping, 'inversedBy') && $mapping->inversedBy && str_ends_with($mapping->inversedBy, 'mediaRelations')) {
                    $masterField = $fieldName;
                    break;
                }
            }
        }

        if ($masterField) {
            try {
                $parent = $this->propertyAccessor->getValue($entity, $masterField);
                if ($parent && method_exists($parent, 'getId')) {
                    $website = $this->findWebsite($parent, $em);
                    $locales = $website ? $website->getConfiguration()->getAllLocales() : [$this->coreLocator->website()->configuration->allLocales];
                    foreach ($locales as $locale) {
                        $pClass = $em->getClassMetadata(get_class($parent))->getName();
                        $pId = $parent->getId();
                        $this->itemsToDelete[] = 'media_relations_'.md5($pClass.'_'.$pId.'_'.$locale);
                        $this->itemsToDelete[] = 'medias_'.md5($pClass.'_'.$pId.'_'.$locale);
                        $this->itemsToDelete[] = 'medias_'.md5($pClass.'_'.implode('_', [$pId]).'_'.$locale);
                    }
                    if ($parent instanceof BaseMediaRelation || $parent instanceof MediaRelationIntl) $this->invalidateMediasCache($parent, $em);
                }
            } catch (\Exception) {}
        }
    }

    /**
     * findPage.
     *
     * @throws MappingException
     */
    private function findPage(object $entity, EntityManagerInterface $em): ?Page
    {
        if ($entity instanceof Page) {
            return $entity;
        }

        $oid = spl_object_hash($entity);
        if (array_key_exists($oid, $this->pages)) {
            return $this->pages[$oid];
        }

        $class = $em->getClassMetadata(get_class($entity))->getName();
        if ($em->getMetadataFactory()->isTransient($class)) {
            return $this->pages[$oid] = null;
        }

        $metadata = $em->getClassMetadata($class);
        foreach (['page', 'layout', 'zone', 'col', 'block', 'mediaRelation'] as $field) {
            if ($metadata->hasAssociation($field)) {
                try {
                    $value = $this->propertyAccessor->getValue($entity, $field);
                    if ($value instanceof Page) {
                        return $this->pages[$oid] = $value;
                    }
                    if ($value instanceof Layout) {
                        $page = $em->getRepository(Page::class)->findOneBy(['layout' => $value]);
                        if ($page) {
                            return $this->pages[$oid] = $page;
                        }
                    }
                    if ($value) {
                        $page = $this->findPage($value, $em);
                        if ($page) {
                            return $this->pages[$oid] = $page;
                        }
                    }
                } catch (\Exception) {
                }
            }
        }

        $masterField = method_exists($class, 'getMasterField') ? $class::getMasterField() : null;
        if ($masterField && $metadata->hasAssociation($masterField)) {
            try {
                $parent = $this->propertyAccessor->getValue($entity, $masterField);
                if ($parent) {
                    return $this->pages[$oid] = $this->findPage($parent, $em);
                }
            } catch (\Exception) {
            }
        }

        return $this->pages[$oid] = null;
    }

    /**
     * findWebsite.
     *
     * @throws MappingException
     */
    private function findWebsite(object $entity, EntityManagerInterface $em): ?Website
    {
        if ($entity instanceof Website) {
            return $entity;
        }

        $oid = spl_object_hash($entity);
        if (array_key_exists($oid, $this->websites)) {
            return $this->websites[$oid];
        }

        $class = $em->getClassMetadata(get_class($entity))->getName();
        if ($em->getMetadataFactory()->isTransient($class)) {
            return $this->websites[$oid] = null;
        }

        $metadata = $em->getClassMetadata($class);
        foreach (['website', 'page', 'zone', 'block', 'col', 'media', 'mediaRelation'] as $field) {
            if ($metadata->hasAssociation($field)) {
                try {
                    $value = $this->propertyAccessor->getValue($entity, $field);
                    if ($value instanceof Website) {
                        return $this->websites[$oid] = $value;
                    }
                    if ($value) {
                        $website = $this->findWebsite($value, $em);
                        if ($website) {
                            return $this->websites[$oid] = $website;
                        }
                    }
                } catch (\Exception) {
                }
            }
        }

        $masterField = method_exists($class, 'getMasterField') ? $class::getMasterField() : null;
        if ($masterField && $metadata->hasAssociation($masterField)) {
            try {
                $parent = $this->propertyAccessor->getValue($entity, $masterField);
                if ($parent) {
                    return $this->websites[$oid] = $this->findWebsite($parent, $em);
                }
            } catch (\Exception) {
            }
        }

        return $this->websites[$oid] = null;
    }
}
