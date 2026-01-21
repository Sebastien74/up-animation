<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Form\Manager\Seo\UrlManager;
use App\Twig\Core\AppRuntime;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ArchiveController.
 *
 * SEO archive URL management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SEO')]
#[Route('/admin-%security_token%/{website}/seo/archive', schemes: '%protocol%')]
class ArchiveController extends AdminController
{
    /**
     * Index archive.
     *
     * @throws InvalidArgumentException
     */
    #[Route('/index', name: 'admin_archive_index', methods: 'GET|POST')]
    public function archive(Request $request, Website $website, AppRuntime $appRuntime)
    {
        $archive = $this->getArchive($website, $appRuntime);

        parent::breadcrumb($request, []);

        return $this->adminRender('admin/page/seo/archive.html.twig', array_merge($this->arguments, [
            'archive' => $archive,
        ]));
    }

    /**
     * Restore Entity.
     *
     * @throws NonUniqueResultException
     */
    #[Route('/restore/{classname}/{id}', name: 'admin_url_archive_restore', methods: 'GET')]
    public function restore(Request $request, UrlManager $urlManager): RedirectResponse
    {
        $website = $this->getWebsite();
        $classname = urldecode($request->get('classname'));
        $entity = $this->coreLocator->em()->getRepository($classname)->find($request->get('id'));

        if (is_object($entity) && method_exists($entity, 'getUrls')) {
            foreach ($entity->getUrls() as $url) {
                /** @var Url $url */
                $existingUrl = $urlManager->getExistingUrl($url, $website->entity, $entity);
                $code = $existingUrl && $existingUrl->getId() !== $url->getId() ? $url->getCode().'-'.uniqid() : $url->getCode();
                $url->setCode($code);
                $url->setArchived(false);
                if ($url->getCode()) {
                    $url->setOnline(true);
                }
                $this->coreLocator->em()->persist($url);
            }
        }

        $queryBuilder = $this->coreLocator->em()->getRepository($classname)->createQueryBuilder('e')
            ->leftJoin('e.urls', 'u')
            ->andWhere('u.archived = :archived')
            ->andWhere('u.website = :website')
            ->setParameter('archived', false)
            ->setParameter('website', $website->entity)
            ->addSelect('u');

        if (method_exists($entity, 'getLevel')) {
            $queryBuilder->andWhere('e.level = :level')->setParameter('level', 1);
        }
        if (method_exists($entity, 'setLevel')) {
            $entity->setLevel(1);
        }
        if (method_exists($entity, 'setParent')) {
            $entity->setParent(null);
        }

        $entity->setPosition(count($queryBuilder->getQuery()->getResult()) + 1);

        $this->coreLocator->em()->persist($entity);
        $this->coreLocator->em()->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Delete Entity.
     *
     * {@inheritdoc}
     */
    #[Route('/delete', name: 'admin_url_archive_delete', methods: 'DELETE')]
    public function delete(Request $request)
    {
        $classname = urldecode($request->get('classname'));
        $entity = $this->coreLocator->em()->getRepository($classname)->find($request->get('id'));
        $this->coreLocator->em()->remove($entity);
        $this->coreLocator->em()->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Initialize archive.
     *
     * @throws InvalidArgumentException
     */
    private function getArchive(Website $website, AppRuntime $appRuntime): array
    {
        $metasData = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();
        $archive = [];
        $ids = [];

        foreach ($metasData as $metadata) {
            $classname = $metadata->getName();
            $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
            $entities = $baseEntity && method_exists($baseEntity, 'getUrls') ?
                $this->coreLocator->em()->getRepository($classname)->findBy(['website' => $website]) : [];
            foreach ($entities as $entity) {
                foreach ($entity->getUrls() as $url) {
                    /** @var Url $url */
                    if ($url instanceof Url && $url->isArchived()) {
                        $interface = $this->getInterface($classname);
                        if (empty($ids[$interface['name']][$entity->getId()])) {
                            $pluralTrans = $this->coreLocator->translator()->trans('plural', [], 'entity_'.$interface['name']);
                            $keyName = 'plural' !== $pluralTrans ? $pluralTrans : ucfirst($interface['name']);
                            $deleteTrans = $this->coreLocator->translator()->trans('delete', [], 'delete_'.$interface['name']);
                            $restoreTrans = $this->coreLocator->translator()->trans('restore', [], 'restore_'.$interface['name']);
                            $code = $url->getCode() ?: uniqid();
                            $uri = Page::class === $interface['classname'] ? '/'.$code
                                : ($appRuntime->routeExist('front_'.$interface['name'].'_view_only.'.$url->getLocale())
                                    ? $this->generateUrl('front_'.$interface['name'].'_view_only.'.$url->getLocale(), ['url' => $code]) : null);
                            $archive[$keyName][] = [
                                'entity' => $entity,
                                'uri' => $uri,
                                'delete' => 'delete' !== $deleteTrans ? $deleteTrans : $this->coreLocator->translator()->trans('Supprimer', [], 'admin'),
                                'restore' => 'restore' !== $restoreTrans ? $restoreTrans : $this->coreLocator->translator()->trans('Restaurer', [], 'admin'),
                                'interface' => $interface,
                            ];
                            $ids[$interface['name']][$entity->getId()] = true;
                        }
                    }
                }
            }
        }

        ksort($archive);

        return array_reverse($archive);
    }
}
