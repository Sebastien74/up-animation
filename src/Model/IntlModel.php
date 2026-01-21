<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Layout\Page;
use App\Service\Interface\CoreLocatorInterface;
use Composer\DependencyResolver\Request;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;

/**
 * IntlModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class IntlModel extends BaseModel
{
    private static array $cache = [];

    /**
     * IntlModel constructor.
     */
    public function __construct(
        public readonly ?object $intl = null,
        public readonly ?string $locale = null,
        public readonly ?bool $haveContent = null,
        public readonly ?int $titleForce = null,
        public readonly ?int $subTitleForce = null,
        public readonly ?string $title = null,
        public readonly ?string $subTitle = null,
        public readonly ?string $subTitlePosition = null,
        public readonly ?string $introduction = null,
        public readonly ?string $body = null,
        public readonly ?string $placeholder = null,
        public readonly ?string $help = null,
        public readonly ?string $error = null,
        public readonly ?string $author = null,
        public readonly ?string $authorType = null,
        public readonly ?string $script = null,
        public readonly ?string $pictogram = null,
        public readonly ?string $video = null,
        public readonly ?bool $active = null,
        public readonly ?string $link = null,
        public readonly ?bool $linkOnline = null,
        public readonly ?Page $linkTargetPage = null,
        public readonly ?bool $linkTargetPageInfill = null,
        public readonly ?bool $linkExternal = null,
        public readonly ?bool $linkBlank = null,
        public readonly ?string $linkWithoutParams = null,
        public readonly ?string $linkParams = null,
        public readonly ?string $linkStyle = null,
        public readonly ?bool $linkAsButton = null,
        public readonly ?bool $linkAsAnchor = null,
        public readonly ?string $linkDataAnchor = null,
        public readonly ?string $linkLabel = null,
        public readonly ?string $linkContent = null,
        public readonly ?bool $linkProtocol = null,
        public readonly ?bool $linkIsEmail = null,
        public readonly ?bool $linkIsPhone = null,
        public readonly ?string $slug = null,
    ) {
    }

    /**
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, bool $query = true, array $options = []): self
    {
        if (!$entity) {
            return new self();
        }

        self::setLocator($coreLocator);

        $entity = is_object($entity) && property_exists($entity, 'entity') && !method_exists($entity, 'getEntity') ? $entity->entity : $entity;
        $locale = $options['locale'] ?? self::$coreLocator->locale();

        if (is_object($entity) && $entity->getId() && isset(self::$cache['response'][get_class($entity)][$entity->getId()][$locale])) {
            return self::$cache['response'][get_class($entity)][$entity->getId()][$locale];
        }

        $asIntl = is_object($entity) && str_contains(strtolower(get_class($entity)), 'intl');
        $intl = !empty($options['intl']) ? $options['intl']
            : (is_object($entity) && method_exists($entity, 'getIntl') ? $entity->getIntl() : ($asIntl ? $entity : ($query && is_object($entity) ? self::intl($entity, $locale) : (is_object($entity) ? self::intlByCollection($entity, $locale) : null))));
        $titleForce = self::getContent('titleForce', $intl) ? self::getContent('titleForce', $intl) : 2;
        $subTitleForce = $titleForce + 1;
        $title = self::getContent('title', $intl);
        $intro = self::getContent('introduction', $intl);
        $body = self::getContent('body', $intl);
        $link = self::intlLink($intl);

        $response = new self(
            intl: $intl,
            locale: self::getContent('locale', $intl),
            haveContent: $title || $intro || $body,
            titleForce: $titleForce ?: 2,
            subTitleForce: $subTitleForce,
            title: !is_numeric($title) && self::escape($title) ? $title : (is_numeric($title) ? $title : null),
            subTitle: self::escape(self::getContent('subTitle', $intl)),
            subTitlePosition: self::getContent('subTitlePosition', $intl),
            introduction: self::escape($intro) ?: null,
            body: self::escape($body) ?: null,
            placeholder: self::getContent('placeholder', $intl),
            help: self::getContent('help', $intl),
            error: self::getContent('error', $intl),
            author: self::getContent('author', $intl),
            authorType: self::getContent('authorType', $intl),
            script: self::getContent('script', $intl),
            pictogram: self::getContent('pictogram', $intl),
            video: self::getContent('video', $intl),
            active: self::getContent('active', $intl, true),
            link: $link->linkPath,
            linkOnline: $link->linkOnline,
            linkTargetPage: $link->linkTargetPage,
            linkTargetPageInfill: $link->linkTargetPageInfill,
            linkExternal: $link->linkExternal,
            linkBlank: $link->linkBlank,
            linkWithoutParams: $link->linkWithoutParams,
            linkParams: $link->linkParams,
            linkStyle: $link->linkStyle,
            linkAsButton: $link->linkAsButton,
            linkAsAnchor: $link->linkAsAnchor,
            linkDataAnchor: $link->linkDataAnchor,
            linkLabel: $link->linkLabel,
            linkContent: $link->linkContent,
            linkProtocol: $link->linkProtocol,
            linkIsEmail: $link->linkIsEmail,
            linkIsPhone: $link->linkIsPhone,
            slug: self::getContent('slug', $intl),
        );

        if (is_object($entity) && $entity->getId()) {
            self::$cache['response'][get_class($entity)][$entity->getId()][$locale] = $response;
        }

        return $response;
    }

    /**
     * To get intls by entities array and by locale.
     *
     * @throws QueryException|NonUniqueResultException|MappingException
     */
    public static function fromEntities(mixed $entity, CoreLocatorInterface $coreLocator, array $ids = [], ?string $locale = null): self
    {
        $locale = $locale ?? self::$coreLocator->locale();
        if (!isset(self::$cache['intls'][get_class($entity)][$locale])) {
            $metadata = self::$coreLocator->metadata($entity, 'intls');
            self::$cache['intls'][get_class($entity)][$locale] = self::$coreLocator->em()->getRepository($metadata->targetEntity)
                ->createQueryBuilder('i')
                ->andWhere('i.'.$metadata->mappedBy.' IN (:ids)')
                ->andWhere('i.locale =  :locale')
                ->setParameter('ids', $ids)
                ->setParameter('locale', $locale)
                ->indexBy('i', 'i.'.$metadata->mappedBy)
                ->getQuery()
                ->getResult();
        }
        $intl = !empty(self::$cache['intls'][get_class($entity)][$locale][$entity->getId()]) ? self::$cache['intls'][get_class($entity)][$locale][$entity->getId()] : null;

        return self::fromEntity($entity, $coreLocator, false, ['intl' => $intl, 'locale' => $locale]);
    }

    /**
     * To get intls by locale.
     *
     * @throws NonUniqueResultException
     */
    public static function intls(mixed $entity, string $fieldName = 'intls', bool $query = true): array
    {
        $metadata = self::$coreLocator->metadata($entity, $fieldName);
        $getter = 'get'.ucfirst($fieldName);

        if ($query && $metadata->mappedBy) {
            return self::$coreLocator->em()->getRepository($metadata->targetEntity)
                ->createQueryBuilder('i')
                ->andWhere('i.'.$metadata->mappedBy.' = :entity')
                ->andWhere('i.locale =  :locale')
                ->setParameter('entity', $entity)
                ->setParameter('locale', self::$coreLocator->locale())
                ->getQuery()
                ->getResult();
        } elseif ($query && $metadata->sourceEntity) {
            $result = self::$coreLocator->em()->getRepository($metadata->sourceEntity)
                ->createQueryBuilder('e')
                ->innerJoin('e.'.$fieldName, $fieldName)
                ->andWhere($fieldName.'.locale =  :locale')
                ->setParameter('locale', self::$coreLocator->locale())
                ->addSelect($fieldName)
                ->getQuery()
                ->getOneOrNullResult();

            return $result ? $result->$getter()->getValues() : [];
        } elseif (!$query && method_exists($entity, $getter)) {
            $values = [];
            foreach ($entity->$getter() as $value) {
                if ($value && method_exists($value, 'getLocale') && self::$coreLocator->locale() === $value->getLocale()) {
                    $values[] = $value;
                }
            }

            return $values;
        }

        return [];
    }

    /**
     * To get intl by locale.
     *
     * @throws NonUniqueResultException
     */
    public static function intl(mixed $entity, string $locale = null): mixed
    {
        $metadata = self::$coreLocator->metadata($entity, 'intls');
        $locale = $locale ?? self::$coreLocator->locale();

        if ($metadata->mappedBy && $entity->getId()) {
            return self::$coreLocator->em()->getRepository($metadata->targetEntity)
                ->createQueryBuilder('i')
                ->andWhere('i.'.$metadata->mappedBy.' = :entity')
                ->andWhere('i.locale =  :locale')
                ->setParameter('entity', $entity)
                ->setParameter('locale', $locale)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return null;
    }

    /**
     * To get intl by locale in a collection.
     */
    public static function intlByCollection(mixed $entity): mixed
    {
        $locale = $locale ?? self::$coreLocator->locale();

        if (method_exists($entity, 'getIntls') && !$entity->getIntls()->isEmpty()) {
            foreach ($entity->getIntls() as $intl) {
                if ($locale === $intl->getLocale()) {
                    return $intl;
                }
            }
        }

        return null;
    }

    /**
     * Get intl Link.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function intlLink(mixed $intl = null): object
    {
        $href = null;
        $targetDomain = null;
        $targetPage = self::getContent('targetPage', $intl);
        $targetLink = self::getContent('targetLink', $intl);
        $targetStyle = self::getContent('targetStyle', $intl);
        $infill = $targetPage && $targetPage->isInFill();
        $isOnline = true;

        if ($targetLink) {
            $href = $targetLink;
            if (!str_contains($href, 'https') && !str_starts_with($href, '/')) {
                $href = '/'.$targetLink;
            }
        } elseif ($targetPage) {
            $page = $targetPage;
            $website = $page->getWebsite();
            $targetDomain = $website ? self::getTargetDomain($website) : null;
            $url = self::intlUrl($page);
            $isOnline = $url->online;
            $asIndex = $page && $page->isAsIndex();
            $urlCode = $asIndex ? null : $url->code;
            if ($url->code && !$targetDomain && !$infill) {
                $href = self::$coreLocator->router()->generate('front_index', [
                    'url' => $targetPage->isAsIndex() ? null : $urlCode,
                ]);
            } elseif ($url->code && $targetDomain && !$infill) {
                $href = $targetDomain.'/'.$urlCode;
            }
        }

        $isEmail = $href && filter_var($href, FILTER_VALIDATE_EMAIL);
        if ($href && $isEmail) {
            $href = 'mailto:'.$href;
        }

        $isPhone = $href && !str_contains($href, 'http') && self::isPhone($href);
        if ($isPhone) {
            $href = str_replace(' ', '', $href);
            $href = 'tel:'.str_replace(' ', '', $href);
        }

        $request = self::$coreLocator->request();
        $haveProtocol = $href && str_contains($href, 'http');
        $currentAnchor = $href && str_contains($href, '#') && !str_contains(trim($href, '/'), '/') ? self::$coreLocator->request()->getUri().$href : false;
        $href = !$haveProtocol && $targetDomain && $href ? ltrim(str_replace($targetDomain, '', $href), '/') : $href;
        $href = $href && !$isPhone && !$isEmail && !str_contains($href, self::$coreLocator->schemeAndHttpHost()) && !str_contains($href, 'http') ? self::$coreLocator->schemeAndHttpHost().$href : $href;
        $style = $targetStyle && str_contains($targetStyle, 'btn') ? 'btn '.$targetStyle : $targetStyle;
        $label = self::getContent('targetLabel', $intl);
        $matches = $href ? explode('?', $href) : [];
        $matchesAnchor = $href ? explode('#', $href) : [];
        $path = $isOnline && '/' === $href ? self::$coreLocator->request()->getSchemeAndHttpHost() : ($isOnline ? $href : null);
        $path = $currentAnchor ?: $path;
        $external = self::externalLink($path);

        return (object) [
            'linkOnline' => $isOnline,
            'linkPath' => $path,
            'linkTargetPage' => $targetPage,
            'linkTargetPageInfill' => $targetPage ? $targetPage->isInfill() : false,
            'linkExternal' => $external || ($path && $intl->isExternalLink()) || ($intl && $intl->isExternalLink()) || ($request && $request->getHost() && $href && !preg_match('/'.$request->getHost().'/', $href)),
            'linkBlank' => $external || ($intl && $intl->isNewTab()),
            'linkWithoutParams' => !empty($matches[0]) ? $matches[0] : null,
            'linkParams' => !empty($matches[0]) ? $matches[0] : null,
            'linkStyle' => $style ?: 'link',
            'linkLabel' => $label,
            'linkContent' => self::getContent('placeholder', $intl),
            'linkProtocol' => $haveProtocol,
            'linkIsEmail' => $isEmail,
            'linkIsPhone' => $isPhone,
            'linkAsButton' => $style && str_contains($style, 'btn'),
            'linkAsAnchor' => $href && str_contains($href, '#'),
            'linkDataAnchor' => $href && str_contains($href, '#') ? '#'.end($matchesAnchor) : '',
        ];
    }

    /**
     * Get intl Url by locale.
     */
    private static function intlUrl(mixed $entity): object
    {
        $online = false;
        $url = null;
        $locale = self::$coreLocator->locale();
        foreach ($entity->getUrls() as $entityUrl) {
            if ($entityUrl->getLocale() === $locale) {
                $url = $entityUrl;
                $online = $url->isOnline();
                break;
            }
        }

        $page = method_exists($entity, 'getTargetPage') ? $entity->getTargetPage() : ($entity instanceof Page ? $entity : null);
        if ($page->isInfill()) {
            $online = true;
        }

        return (object) [
            'code' => $url ? $url->getCode() : null,
            'online' => $url ? $online : false,
        ];
    }

    /**
     * As external link.
     */
    private static function externalLink(?string $path = null): bool
    {
        $domainMatches = self::$coreLocator->request() instanceof Request ? explode('.', self::$coreLocator->request()->getHost()) : [];
        $domainSimple = !empty($domainMatches) ? $domainMatches[array_key_first($domainMatches)].'.' : '';
        return $domainSimple && $path && str_contains($path, 'http') && !str_contains($path, $domainSimple);
    }
}
