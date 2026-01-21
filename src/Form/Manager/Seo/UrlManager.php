<?php

declare(strict_types=1);

namespace App\Form\Manager\Seo;

use App\Entity\Core\Website;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Service\Core\Urlizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;

/**
 * UrlManager.
 *
 * Manage Seo Url admin form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => UrlManager::class, 'key' => 'seo_url_form_manager'],
])]
class UrlManager
{
    /**
     * UrlManager constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Post Urls.
     *
     * @throws NonUniqueResultException
     */
    public function post(FormInterface $form, Website $website): void
    {
        $entity = $form->getData();
        if (method_exists($entity, 'getUrls')) {
            if (0 === $entity->getUrls()->count()) {
                $url = new Url();
                $url->setLocale($website->getConfiguration()->getLocale());
                $entity->addUrl($url);
            }
            foreach ($entity->getUrls() as $url) {
                $this->addUrl($url, $website, $entity);
            }
        }
    }

    /**
     * Add Url.
     *
     * @throws NonUniqueResultException
     */
    public function addUrl(Url $url, Website $website, $entity): void
    {
        $defaultLocale = $website->getConfiguration()->getLocale();
        $code = !$url->getCode() && $url->getLocale() === $defaultLocale && method_exists($entity, 'getAdminName')
            ? Urlizer::urlize($entity->getAdminName())
            : Urlizer::urlize($url->getCode());
        $url->setCode($code);

        if (method_exists($entity, 'isInFill') && $entity->isInFill()) {
            $code = null;
        }

        if ($code && $url->getLocale() === $defaultLocale) {
            $existing = $this->getExistingUrl($url, $website, $entity);
            $code = $existing && $existing->getId() !== $url->getId() ? $code.'-'.uniqid() : $code;
            $url->setCode($code);
        }

        if (!$url->getSeo()) {
            $seo = new Seo();
            $seo->setUrl($url);
            $url->setSeo($seo);
            $this->entityManager->persist($seo);
        }

        $url->setWebsite($website);

        if (method_exists($entity, 'isInFill') && $entity->isInFill()) {
            $url->setOnline(false);
        }

        $this->entityManager->persist($url);
    }

    /**
     * Synchronize locale Url.
     */
    public function synchronizeLocales($entity, ?Website $website = null): void
    {
        if ($website && method_exists($entity, 'getUrls') && $entity->getUrls()->count() > 0) {
            foreach ($website->getConfiguration()->getAllLocales() as $locale) {
                $existing = false;
                foreach ($entity->getUrls() as $url) {
                    /** @var Url $url */
                    if ($url->getLocale() === $locale) {
                        $existing = true;
                    }
                }
                if (!$existing) {
                    $url = new Url();
                    $url->setLocale($locale);
                    $entity->addUrl($url);
                    $this->entityManager->persist($entity);
                    $this->entityManager->flush();
                    $this->entityManager->refresh($entity);
                }
            }
        }
    }

    /**
     * Get existing Url.
     *
     * @throws NonUniqueResultException
     */
    public function getExistingUrl(Url $url, Website $website, $entity, $classname = null): ?Url
    {
        if ($entity instanceof Seo) {
            $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
            foreach ($metasData as $metadata) {
                $classname = $metadata->getName();
                $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
                if ($baseEntity && method_exists($baseEntity, 'getUrls')) {
                    $entity = $this->entityManager->getRepository($classname)->createQueryBuilder('e')
                        ->leftJoin('e.urls', 'u')
                        ->andWhere('u.id = :id')
                        ->setParameter('id', $url->getId())
                        ->addSelect('u')
                        ->getQuery()
                        ->getOneOrNullResult();
                    if ($entity) {
                        break;
                    }
                }
            }
        }

        $locale = $url->getLocale();
        $classname = $this->entityManager->getClassMetadata(get_class($entity))->getName();
        $findEntity = $url->getCode() ? $this->entityManager->getRepository($classname)->createQueryBuilder('e')
            ->leftJoin('e.urls', 'u')
            ->andWhere('u.code = :code')
            ->andWhere('u.id != :id')
            ->andWhere('u.locale = :locale')
            ->andWhere('u.website = :website')
            ->andWhere('u.archived = :archived')
            ->setParameter('code', Urlizer::urlize($url->getCode()))
            ->setParameter('id', $url->getId())
            ->setParameter('locale', $locale)
            ->setParameter('website', $website)
            ->setParameter('archived', false)
            ->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult() : null;

        if (is_object($findEntity) && method_exists($findEntity, 'getUrls')) {
            foreach ($findEntity->getUrls() as $url) {
                /** @var Url $url */
                if ($url->getLocale() === $locale) {
                    return $url;
                }
            }
        }

        return null;
    }
}
