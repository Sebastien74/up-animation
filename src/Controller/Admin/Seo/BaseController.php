<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Model;
use App\Entity\Seo\Url;
use App\Service\Content\SeoInterface;
use App\Service\Content\SeoService;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * BaseController.
 *
 * SEO base controller
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseController extends AdminController
{
    /**
     * BaseController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Get Entities for tree.
     */
    protected function getEntities(Request $request, Website $website, SeoInterface $seoService): void
    {
        $currentUrl = !empty($this->arguments['currentUrl']) ? $this->arguments['currentUrl'] : null;
        $metasData = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();

        foreach ($metasData as $metadata) {
            $classname = $metadata->getName();
            $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
            $entities = [];
            if ($baseEntity && method_exists($baseEntity, 'getUrls') && method_exists($baseEntity, 'getWebsite')) {
                $this->setEmptyUrls($request, $website, $classname, $baseEntity);
                $statement = $this->coreLocator->em()->getRepository($classname)->createQueryBuilder('e')
                    ->leftJoin('e.website', 'w')
                    ->leftJoin('e.urls', 'u')
                    ->leftJoin('u.seo', 's')
                    ->andWhere('e.website = :website')
                    ->andWhere('u.locale = :locale')
                    ->andWhere('u.archived = :archived')
                    ->setParameter('website', $website)
                    ->setParameter('locale', $request->get('entitylocale'))
                    ->setParameter('archived', false)
                    ->addSelect('w')
                    ->addSelect('u')
                    ->addSelect('s');
                if (method_exists($baseEntity, 'getIntls')) {
                    $statement->leftJoin('e.intls', 'i')
                        ->addSelect('i');
                }
                if (method_exists($baseEntity, 'getMediaRelations')) {
                    $statement->leftJoin('e.mediaRelations', 'mr')
                        ->addSelect('mr');
                }
                if ($baseEntity instanceof Page) {
                    $statement->andWhere('e.slug NOT IN (:slugs)')
                        ->setParameter('slugs', ['error', 'components']);
                }
                $entities = $statement->getQuery()->getResult();
            }

            foreach ($entities as $entity) {
                foreach ($entity->getUrls() as $url) {
                    if ($url instanceof Url) {
                        if ($url->getLocale() === $request->get('entitylocale')) {
                            $this->getUrls($url, $entity, $seoService, $currentUrl);
                        }
                    }
                }
            }
        }

        $this->getModels($request, $website);

        if (!empty($this->arguments['entities']['page'])) {
            $this->arguments['entities']['page'] = $this->getTree($this->arguments['entities']['page']);
        }
    }

    /**
     * To add URL if not existing.
     */
    private function setEmptyUrls(Request $request, Website $website, string $classname, mixed $baseEntity): void
    {
        //        $statement = $this->coreLocator->em()->getRepository($classname)->createQueryBuilder('e')
        //            ->leftJoin('e.website', 'w')
        //            ->leftJoin('e.urls', 'u')
        //            ->andWhere('e.website = :website')
        //            ->setParameter('website', $website)
        //            ->addSelect('w')
        //            ->addSelect('u');
        //        if ($baseEntity instanceof Page) {
        //            $statement->andWhere('e.slug NOT IN (:slugs)')
        //                ->setParameter('slugs', ['error', 'components']);
        //        }
        //        $entities = $statement->getQuery()->getResult();
        //
        //        foreach ($entities as $entity) {
        //            $urlExisting = false;
        //            $isArchived = false;
        //            foreach ($entity->getUrls() as $url) {
        //                if ($url->getLocale() === $request->get('entitylocale')) {
        //                    $urlExisting = true;
        //                }
        //                if ($url->isArchived()) {
        //                    $isArchived = true;
        //                }
        //            }
        //            if (!$urlExisting && !$isArchived && !empty($request->get('entitylocale'))) {
        //                $url = new Url();
        //                $url->setCreatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        //                $url->setCreatedBy($this->getUser());
        //                $url->setLocale($request->get('entitylocale'));
        //                $url->setWebsite($website);
        //                $entity->addUrl($url);
        //                $this->coreLocator->em()->persist($entity);
        //                $this->coreLocator->em()->flush();
        //            }
        //        }
    }

    /**
     * To not display all pages error.
     */
    protected function setPagesError(): void
    {
        if (!empty($this->arguments['entities']['page'])) {
            $errorsPages = [];
            foreach ($this->arguments['entities']['page'] as $key => $pages) {
                foreach ($pages as $keyPage => $page) {
                    $isConfigObject = is_object($page) && property_exists($page, 'entity');
                    $page = $isConfigObject ? $page->entity : $page;
                    if ('error.html.twig' === $page->getTemplate() && in_array($page->getId(), $errorsPages)) {
                        unset($this->arguments['entities']['page'][$key][$keyPage]);
                    } elseif ('error.html.twig' === $page->getTemplate()) {
                        $errorsPages[] = $page->getId();
                    }
                }
            }
        }
    }

    /**
     * Get all Url.
     */
    private function getUrls(Url $url, mixed $entity, SeoService $seoService, ?Url $currentUrl = null): void
    {
        $interfaceName = $entity::getInterface()['name'];

        if ($url === $currentUrl) {
            $this->arguments['currentCategory'] = $interfaceName;
        }

        $classname = $this->coreLocator->em()->getClassMetadata(get_class($entity))->getName();

        if (empty($this->arguments['mappedEntities']) || !in_array($classname, $this->arguments['mappedEntities'])) {
            $this->arguments['mappedEntities'][] = $classname;
        }

        /* To get model by relation entity */
        foreach ($seoService->getRelationsModels() as $relation) {
            $getter = 'get'.ucfirst($relation);
            if (method_exists($entity, $getter) && $entity->$getter()) {
                $relation = $entity->$getter();
                $relationClassname = str_replace('Proxies\__CG__\\', '', get_class($relation));
                if (empty($this->arguments['relationsMappedEntities'][$relationClassname][$relation->getId()])) {
                    $this->arguments['relationsMappedEntities'][$relationClassname][$relation->getId()] = [
                        'childClassname' => $classname,
                        'relation' => $relation,
                    ];
                }
            }
        }

        $this->arguments['entities'][$interfaceName][] = (object) [
            'classname' => $classname,
            'title' => $entity->getAdminName(),
            'url' => $url,
            'active' => $url === $currentUrl,
            'seo' => $url->getSeo(),
            'entity' => $entity,
        ];
    }

    /**
     * Get models.
     */
    private function getModels(Request $request, Website $website): void
    {
        $mappedEntities = !empty($this->arguments['mappedEntities']) ? $this->arguments['mappedEntities'] : [];
        foreach ($mappedEntities as $classname) {
            $interface = $this->getInterface($classname);
            if (isset($interface['configuration']) && $interface['configuration']->card) {
                $model = $this->getModel($request, $website, $classname);
                $this->arguments['models'][$model->getId()] = $model;
                ksort($this->arguments['models']);
            }
        }

        $relationsMappedEntities = !empty($this->arguments['relationsMappedEntities']) ? $this->arguments['relationsMappedEntities'] : [];
        foreach ($relationsMappedEntities as $classname => $entities) {
            foreach ($entities as $configuration) {
                $entity = $configuration['relation'];
                $model = $this->getModel($request, $website, $classname, $entity, $configuration['childClassname'], $entity->getAdminName());
                $this->arguments['models'][$model->getId()] = $model;
                ksort($this->arguments['models']);
            }
        }
    }

    /**
     * Get models.
     */
    private function getModel(
        Request $request,
        Website $website,
        string $classname,
        mixed $entity = null,
        ?string $childClassName = null,
        ?string $adminName = null,
    ): ?Model {

        $entityId = $entity && is_object($entity) && $entity->getId() ? $entity->getId() : null;

        $model = $this->coreLocator->em()->getRepository(Model::class)->findOneBy([
            'website' => $website,
            'className' => $classname,
            'childClassName' => $childClassName,
            'entityId' => $entityId,
            'locale' => $request->get('entitylocale'),
        ]);

        if (!$model) {
            $model = new Model();
            $model->setLocale($request->get('entitylocale'))
                ->setClassName($classname)
                ->setChildClassName($childClassName)
                ->setAdminName($adminName)
                ->setEntityId($entityId)
                ->setWebsite($website);
            $this->coreLocator->em()->persist($model);
            $this->coreLocator->em()->flush();
        }

        return $model;
    }
}
