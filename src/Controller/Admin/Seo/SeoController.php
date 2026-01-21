<?php

declare(strict_types=1);

namespace App\Controller\Admin\Seo;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Entity\Seo\Seo;
use App\Entity\Seo\Url;
use App\Form\Type\Seo\SeoType;
use App\Service\Content\SeoService;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SeoController.
 *
 * SEO management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_SEO')]
#[Route('/admin-%security_token%/{website}/seo', schemes: '%protocol%')]
class SeoController extends BaseController
{
    protected ?string $class = Seo::class;
    protected ?string $formType = SeoType::class;

    /**
     * Edit Seo.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|\ReflectionException
     */
    #[Route('/edit/{entitylocale}/{url}', name: 'admin_seo_edit', defaults: ['url' => null], methods: 'GET|POST')]
    public function editSeo(Request $request, SeoService $seoService): Response
    {
        $website = $this->getWebsite();

        $this->template = 'admin/page/seo/edit.html.twig';

        $this->setEntity($request, $website->entity, $seoService);
        $this->getEntities($request, $website->entity, $seoService);
        $this->setPagesError();

        if (isset($this->arguments['seo']['haveIndexPage']) && $this->arguments['seo']['haveIndexPage']) {
            $this->formOptions['have_index_page'] = $this->arguments['seo']['haveIndexPage'];
        }

        return parent::edit($request);
    }

    /**
     * Set entity.
     *
     * @throws InvalidArgumentException|NonUniqueResultException|MappingException|\ReflectionException
     */
    private function setEntity(Request $request, Website $website, SeoService $seoService): void
    {
        $url = null;

        if (!$request->get('url')) {
            $page = $this->coreLocator->em()->getRepository(Page::class)->findOneBy([
                'asIndex' => true,
                'website' => $website,
            ]);

            if (!$page) {
                throw $this->createNotFoundException($this->coreLocator->translator()->trans("Vous devez définir une page d'accueil !!", [], 'admin'));
            }

            $url = $this->getUrl($request, $page);
        } elseif ($request->get('url')) {
            $url = $this->coreLocator->em()->getRepository(Url::class)->find($request->get('url'));
        }

        if ($url instanceof Url && $url->getLocale() !== $request->get('entitylocale')) {
            throw $this->createNotFoundException();
        }

        $this->arguments['currentUrl'] = $url;

        if ($url) {
            $this->entity = $this->setSeo($url, $website);
            $this->arguments['seo'] = $seoService->execute($url, $this->getParentEntity($url));
        }
    }

    /**
     * Get Url.
     */
    private function getUrl(Request $request, mixed $entity): ?Url
    {
        foreach ($entity->getUrls() as $url) {
            /** @var Url $url */
            if ($url->getLocale() === $request->get('entitylocale')) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Set Seo.
     */
    private function setSeo(Url $url, Website $website): ?Seo
    {
        if (!$url->getWebsite()) {
            $url->setWebsite($website);
        }

        $seo = $url->getSeo();
        if ($seo) {
            if (!$seo->getUrl()) {
                $seo->setUrl($url);
            }

            return $url->getSeo();
        }

        $seo = new Seo();
        $seo->setUrl($url);
        $url->setSeo($seo);

        $this->coreLocator->em()->persist($seo);
        $this->coreLocator->em()->flush();

        return $seo;
    }

    /**
     * Set parent entity.
     *
     * @throws NonUniqueResultException
     */
    private function getParentEntity(Url $url): ?object
    {
        $metasData = $this->coreLocator->em()->getMetadataFactory()->getAllMetadata();

        foreach ($metasData as $metadata) {
            $classname = $metadata->getName();
            $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $classname() : null;
            if ($baseEntity && method_exists($baseEntity, 'getUrls') && method_exists($baseEntity, 'getWebsite')) {
                $entity = $this->coreLocator->em()->getRepository($classname)
                    ->createQueryBuilder('e')
                    ->leftJoin('e.website', 'w')
                    ->leftJoin('e.urls', 'u')
                    ->andWhere('u.id = :id')
                    ->setParameter('id', $url->getId())
                    ->addSelect('w')
                    ->addSelect('u')
                    ->getQuery()
                    ->getOneOrNullResult();
                if ($entity) {
                    return $entity;
                }
            }
        }

        return null;
    }
}
