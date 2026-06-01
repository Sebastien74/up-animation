<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Core\Website;
use App\Entity\Information\Information;
use App\Entity\Layout\Block;
use App\Entity\Layout\Layout;
use App\Entity\Layout\Page;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Form\Form;
use App\Entity\Seo\Model;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use ReflectionException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * BaseModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseModel extends FunctionModel
{
    protected static ?CoreLocatorInterface $coreLocator = null;
    protected static array $intlCache = [];
    protected static array $baseCache = [];
    private static array $methodCache = [];
    private static array $cachePools = [];

    /**
     * To set CoreLocator.
     */
    protected static function setLocator(CoreLocatorInterface $coreLocator): void
    {
        self::$coreLocator = $coreLocator;
    }

    /**
     * To get CoreLocator.
     */
    protected static function getCoreLocator(): ?CoreLocatorInterface
    {
        if (null === self::$coreLocator) {
            throw new \RuntimeException('CoreLocator not set. Call setCoreLocator before using this method.');
        }

        return self::$coreLocator;
    }

    /**
     * To manage cache pull.
     *
     * @throws InvalidArgumentException
     */
    protected static function cachePool(mixed $entity, string $method, mixed $response = null): mixed
    {
        $isFront = self::$coreLocator->inFront();

        if ($isFront) {
            $cacheKey = Urlizer::urlize(get_class($entity)).'-'.$entity->getId();
            if (!isset(self::$cachePools[$cacheKey])) {
                self::$cachePools[$cacheKey] = new FilesystemAdapter('', 0, self::$coreLocator->cacheDir().'/'.$cacheKey);
            }
            $cachePool = self::$cachePools[$cacheKey];
            $keyName = str_replace('-', '', $cacheKey);
            $entityCachePool = $cachePool->getItem($keyName);
            if ('GET' === $method) {
                if ($entityCachePool->isHit()) {
                    return $entityCachePool->get();
                }
            } elseif ('GENERATE' === $method) {
                $entityCachePool->set($response);
                $cachePool->save($entityCachePool);
            }
        }

        return $response;
    }

    /**
     * To get content.
     *
     * @throws NonUniqueResultException|MappingException
     */
    protected static function getContent(
        string $property,
        mixed $entity = null,
        bool $asBool = false,
        bool $asArray = false,
        bool $parseCollection = false,
    ): mixed {

        $content = null;

        if (is_object($entity)) {

            $className = get_class($entity);
            if (!isset(self::$methodCache[$className][$property])) {
                $getterIs = 'is'.ucfirst($property);
                $getterGet = 'get'.ucfirst($property);
                self::$methodCache[$className][$property] = method_exists($entity, $getterIs) ? $getterIs
                    : (method_exists($entity, $getterGet) ? $getterGet : (property_exists($entity, $property) ? $property : false));
            }

            $getter = self::$methodCache[$className][$property];
            if (false === $getter) {
                return null;
            }

            $asModel = str_contains($className, 'Model') && !$entity instanceof Model;
            $contentToCheck = $asModel ? $entity->$getter : (method_exists($entity, $getter) ? $entity->$getter() : $entity->$getter);
            if (is_numeric($contentToCheck) && !$asBool) {
                $content = $contentToCheck;
            } elseif ($contentToCheck && (is_object($contentToCheck) || is_array($contentToCheck))) {
                $content = $contentToCheck;
            } elseif ($contentToCheck && is_string($contentToCheck) && (strlen(strip_tags($contentToCheck)) > 0 || str_contains($contentToCheck, '<iframe'))) {
                $content = $contentToCheck;
            } elseif ($contentToCheck && !is_string($contentToCheck)) {
                $content = $contentToCheck;
            }
        }

        if ($asBool && !$content) {
            $content = false;
        }

        if ($asArray && !$content) {
            $content = [];
        }

        if ($parseCollection && $content instanceof PersistentCollection) {
            $collection = [];
            foreach ($content as $item) {
                $collection[] = EntityModel::fromEntity($item, self::$coreLocator)->response;
            }
            $content = $collection;
        }

        if ($content && is_string($content) && str_contains($content, '<table') && !str_contains($content, 'table-responsive')) {
            $content = preg_replace('/<table(.*?)>/is', '<div class="table-responsive"><table class="table table-striped"$1>', $content);
            $content = str_replace('</table>', '</table></div>', $content);
            $content = preg_replace('/height=".*?"/i', '', $content);
            $content = preg_replace('/height:.*?;/i', '', $content);
        }

        return $content;
    }

    /**
     * To get content intl.
     *
     * @throws NonUniqueResultException|MappingException
     */
    protected static function getContentIntl(
        string $property,
        string $locale,
        ?object $entity,
        bool $asBool = false,
        bool $asArray = false,
    ): mixed {
        $intl = $entity && isset(self::$intlCache[get_class($entity)][$locale]) ? self::$intlCache[get_class($entity)][$locale] : null;

        if ($entity && method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $intl) {
                if ($locale === $intl->getLocale()) {
                    $intl = self::$intlCache[get_class($entity)][$locale] = $intl;
                    break;
                }
            }
        } elseif ($entity && method_exists($entity, 'getIntl')) {
            $intl = self::$intlCache[get_class($entity)][$locale] = $entity->getIntl();
        }

        return self::getContent($property, $intl, $asBool, $asArray);
    }

    /**
     * To get a table name.
     */
    protected static function getTableName(mixed $entity): ?string
    {
        $tableName = is_object($entity) && method_exists($entity, 'getInterface') && !empty($entity::getInterface()['table_name'])
            ? $entity::getInterface()['table_name']
            : null;
        if (!$tableName) {
            throw new \RuntimeException('You must configure the entity interface db_table.');
        }

        return $tableName;
    }

    /**
     * Get meta data.
     */
    protected static function metadata(mixed $entity, array $excluded = ['id']): array
    {
        $result = [];
        $metadata = self::$coreLocator->em()->getClassMetadata(get_class($entity));
        foreach ($metadata->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $excluded)) {
                $getter = 'get'.ucfirst($fieldName);
                $getter = method_exists($entity, $getter) ? $getter : 'is'.ucfirst($fieldName);
                $result[$fieldName] = $entity->$getter();
            }
        }
        return $result;
    }

    /**
     * To get cache.
     */
    protected static function cache(mixed $entity, string $property, array &$cache): mixed
    {
        if ($entity->getId() && !empty($cache[get_class($entity)][$property][$entity->getId()])) {
            return $cache[get_class($entity)][$property][$entity->getId()];
        }
        $getMethod = 'get'.ucfirst($property);
        $isMethod = 'is'.ucfirst($property);
        $value = method_exists($entity, $getMethod) ? $entity->$getMethod()
            : (method_exists($entity, $isMethod) ? $entity->$isMethod() : null);
        $cache[get_class($entity)][$property][$entity->getId()] = $value;
        return $value;
    }

    /**
     * To escape string.
     */
    public static function escape(?string $string = null): ?string
    {
        return self::$coreLocator->unescape($string);
    }

    /**
     * To get cache.
     *
     * @throws ReflectionException
     */
    protected static function jsonCache(mixed $entity, string $locale, string $model, array $options = [])
    {

        $localeData = self::cacheData($entity, $locale, $model, $options);
        return $model::modelCache($localeData);
    }

    /**
     * To set a cache file.
     */
    private static function cacheData(mixed $entity, string $locale, string $modelClassname, array $options = [])
    {
        $filesystem = new Filesystem();
        $entityId = method_exists($entity, 'getId') ? $entity->getId() : $entity->id;
        $entityMatches = explode('-', Urlizer::urlize(get_class($entity)));
        $modelMatches = explode('-', Urlizer::urlize($modelClassname));
        $dirname = self::$coreLocator->cacheDir().'/'.end($entityMatches).'-'.end($modelMatches).'-'.$entityId.'-'.$locale.'.cache.json';

        if (!$filesystem->exists($dirname)) {
            $model = EntityModel::class === $modelClassname
                ? $modelClassname::fromEntity($entity, self::$coreLocator, $options)
                : $modelClassname::fromEntity($entity, self::$coreLocator, $locale);
            $reflector = new \ReflectionClass($model);
            $properties = $reflector->getProperties(\ReflectionProperty::IS_PUBLIC);
            $cacheData = [];
            foreach ($properties as $property) {
                $name = $property->name;
                $cacheData[$entityId][$locale][$name] = $model->$name;
            }
            $fp = fopen($dirname, 'w');
            fwrite($fp, json_encode($cacheData, JSON_PRETTY_PRINT));
            fclose($fp);
        }
        $json = (array) json_decode(file_get_contents($dirname));
        $entityData = isset($json[$entityId]) ? (array) $json[$entityId] : [];

        return $entityData[$locale] ?? false;
    }

    /**
     * Get a target domain.
     */
    protected static function getTargetDomain(Website $website): ?string
    {
        $request = self::$coreLocator->request();
        foreach ($website->getConfiguration()->getDomains() as $domain) {
            $sameDomain = $domain->getName() === $request->getHost();
            if ($sameDomain && $domain->getLocale() === $request->getLocale() && $domain->isAsDefault()) {
                $protocol = $request->isSecure() ? 'https' : 'http';
                return $protocol.'://'.$domain->getName();
            }
        }

        return null;
    }

    /**
     * Get Layout.
     *
     * @throws NonUniqueResultException
     */
    protected static function getLayout(mixed $entity): ?Layout
    {
        $classname = $entity ? str_replace('Proxies\__CG__\\', '', get_class($entity)) : null;
        $isCustomLayout = $entity && method_exists($entity, 'isCustomLayout');
        $execute = ($entity && !method_exists($entity, 'isCustomLayout')) || ($isCustomLayout && $entity->isCustomLayout());

        if ($execute && method_exists($entity, 'getLayout') && $entity->getLayout()) {
            if (!empty(self::$baseCache[$classname][$entity->getId()])) {
                return self::$baseCache[$classname][$entity->getId()];
            } elseif (isset(self::$baseCache[$classname]) && array_key_exists($entity->getId(), self::$baseCache[$classname])) {
                return null;
            }
            $qb = self::$coreLocator->em()->getRepository(Layout::class)
                ->createQueryBuilder('l')
                ->leftJoin('l.zones', 'z')
                ->leftJoin('z.cols', 'c')
                ->leftJoin('c.blocks', 'b')
                ->leftJoin('b.blockType', 'bt');
            if ($isCustomLayout || $entity instanceof Page) {
                $qb->innerJoin('b.action', 'ac')
                    ->innerJoin('b.actionIntls', 'aci')
                    ->innerJoin('b.mediaRelations', 'mr')
                    ->innerJoin('b.intls', 'i');
            }
            $layout = $qb->andWhere('l.id =  :id')
                ->setParameter('id', $entity->getLayout()->getId())
                ->addSelect('z')
                ->addSelect('c')
                ->addSelect('b')
                ->addSelect('bt')
                ->getQuery()
                ->enableResultCache(3600, 'layout_'.($entity->getLayout()->getId()))
                ->getOneOrNullResult();

            if ($layout instanceof Layout) {
                self::primeBlockIntls($layout);
            }

            self::$baseCache[$classname][$entity->getId()] = $layout ?: $entity->getLayout();

            return self::$baseCache[$classname][$entity->getId()];
        }

        return null;
    }

    /**
     * Batch-load the current-locale block translations to avoid one SELECT per block
     * when intlByCollection reads each block intl during layout rendering (N+1).
     */
    protected static function primeBlockIntls(Layout $layout): void
    {
        $blockIds = [];
        foreach ($layout->getZones() as $zone) {
            foreach ($zone->getCols() as $col) {
                foreach ($col->getBlocks() as $block) {
                    $blockIds[] = $block->getId();
                }
            }
        }

        if ([] === $blockIds) {
            return;
        }

        self::$coreLocator->em()->getRepository(Block::class)->createQueryBuilder('b')
            ->leftJoin('b.intls', 'i')->addSelect('i')
            ->andWhere('b.id IN (:ids)')->setParameter('ids', $blockIds)
            ->andWhere('i.locale = :locale OR i.locale IS NULL')->setParameter('locale', self::$coreLocator->locale())
            ->getQuery()
            ->getResult();
    }

    /**
     * Get style classes.
     *
     * @throws NonUniqueResultException|MappingException
     */
    protected static function styleClass(mixed $entity, array $default = []): string
    {
        $fontSize = self::getContent('fontSize', $entity);
        $class = $fontSize ? 'fz-'.$fontSize.' ' : (isset($default['fontSize']) ? $default['fontSize'].' ' : '');
        $fontWeight = self::getContent('fontWeight', $entity);
        $class .= $fontWeight ? 'fw-'.$fontWeight.' ' : (isset($default['fontWeight']) ? $default['fontWeight'].' ' : '');
        $fontFamily = self::getContent('fontFamily', $entity);
        $class .= $fontFamily ? 'ff-'.$fontFamily.' ' : (isset($default['fontFamily']) ? $default['fontFamily'].' ' : '');
        $color = self::getContent('color', $entity);
        $class .= $color ? 'text-'.$color.' ' : (isset($default['color']) ? $default['color'].' ' : '');
        $uppercase = self::getContent('uppercase', $entity);
        $class .= $uppercase || isset($default['uppercase']) ? ' text-uppercase ' : '';
        $italic = self::getContent('italic', $entity);
        $class .= $italic || isset($default['italic']) ? 'text-italic ' : '';
        $zIndex = self::getContent('zIndex', $entity);
        $class .= $zIndex ? 'z-index'.$zIndex.' ' : (isset($default['zIndex']) ? $default['zIndex'].' ' : '');

        return trim($class);
    }

    /**
     * To convert string to camel case.
     */
    protected static function stringToCamelCase(?string $string = null): ?string
    {
        $string = str_replace('-', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return lcfirst($string);
    }

    /**
     * To get Form Page.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|InvalidArgumentException
     */
    protected static function getFormPage(mixed $model): ?string
    {
        $entity = $model->entity;
        $pageUrl = null;
        $route = self::$coreLocator->request() ? self::$coreLocator->request()->attributes->get('_route') : null;

        if ($route && str_contains($route, '_view')) {
            $form = self::getContent('form', $entity);
            $page = $form ? self::$coreLocator->em()->getRepository(Page::class)->findByAction(
                self::$coreLocator->website()->entity,
                self::$coreLocator->locale(),
                Form::class,
                $form->getId()
            ) : null;
            $page = $page ? ViewModel::fromEntity($page, self::$coreLocator, [
                'disabledLayout' => true,
                'disabledIntl' => true,
                'disabledMedias' => true,
            ]) : null;
            $pageUrl = $page && $page->online ? $page->url.'?category='.$model->interfaceName.'&code='.$model->id : null;
        }

        return $pageUrl;
    }
}
