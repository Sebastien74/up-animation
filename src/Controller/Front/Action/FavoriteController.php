<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Module\Catalog\Listing;
use App\Entity\Module\Catalog\Product;
use App\Model\Module\ProductModel;
use App\Service\Pdf\ProductPdfRenderer;
use App\Twig\Content\FavoritesRuntime;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * FavoriteController.
 *
 * Front favorites listing (cookie-backed).
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FavoriteController extends FrontController
{
    /**
     * Index.
     *
     * @throws ContainerExceptionInterface|InvalidArgumentException|MappingException|NonUniqueResultException|NotFoundExceptionInterface|QueryException|ReflectionException|Exception
     */
    #[Route([
        'fr' => '/mon-espace/favoris/liste',
        'fr_ch' => '/mon-espace/favoris/liste',
        'en' => '/my-space/favorites/list',
    ], name: 'front_favorites_index', methods: 'GET', schemes: '%protocol%', priority: 300)]
    public function index(Request $request, FavoritesRuntime $favoritesRuntime): Response
    {
        $website = $this->getWebsite();
        $websiteTemplate = $website->configuration->template;
        $locale = $request->getLocale();

        $ids = $favoritesRuntime->favoritesIds();

        $products = [];
        if (!empty($ids)) {
            $entities = $this->coreLocator->em()->getRepository(Product::class)
                ->findByIds($website->entity, $locale, $ids);

            $urlsIndex = $this->coreLocator->listingService()
                ->indexesPages($locale, Listing::class, Product::class);

            foreach ($entities as $entity) {
                $products[] = ProductModel::fromEntity($entity, $this->coreLocator, [
                    'urlsIndex' => $urlsIndex,
                    'disabledProducts' => true,
                ]);
            }
        }

        return $this->render('front/'.$websiteTemplate.'/actions/catalog/favorite.html.twig', array_merge([
            'websiteTemplate' => $websiteTemplate,
            'products' => $products,
            'count' => count($products),
        ], $this->defaultArgs($website)));
    }

    /**
     * Print all favorites as a single PDF.
     *
     * Silently ignores favorite IDs that no longer match a published product.
     *
     * @throws ContainerExceptionInterface|InvalidArgumentException|MappingException|NonUniqueResultException|NotFoundExceptionInterface|QueryException|ReflectionException|Exception
     */
    #[Route([
        'fr' => '/mon-espace/favoris/liste/print',
        'fr_ch' => '/mon-espace/favoris/liste/print',
        'en' => '/my-space/favorites/list/print',
    ], name: 'front_favorites_print', methods: 'GET', schemes: '%protocol%', priority: 310)]
    public function print(
        Request $request,
        FavoritesRuntime $favoritesRuntime,
        ProductPdfRenderer $renderer,
    ): Response {
        $website = $this->getWebsite();
        $locale = $request->getLocale();
        $ids = $favoritesRuntime->favoritesIds();

        if (empty($ids)) {
            return new RedirectResponse($this->generateUrl('front_favorites_index'));
        }

        $entities = $this->coreLocator->em()->getRepository(Product::class)
            ->findByIds($website->entity, $locale, $ids);

        if (empty($entities)) {
            return new RedirectResponse($this->generateUrl('front_favorites_index'));
        }

        $urlsIndex = $this->coreLocator->listingService()
            ->indexesPages($locale, Listing::class, Product::class);

        $products = [];
        $productUrls = [];
        foreach ($entities as $entity) {
            $product = ProductModel::fromEntity($entity, $this->coreLocator, [
                'urlsIndex' => $urlsIndex,
                'disabledProducts' => true,
            ]);
            $products[] = $product;
            $productId = property_exists($product, 'id') ? (int) $product->id : 0;
            $urlCode = property_exists($product, 'urlCode') ? $product->urlCode : null;
            if ($productId > 0 && is_string($urlCode) && '' !== $urlCode) {
                $productUrls[$productId] = $this->generateUrl(
                    'front_catalogproduct_view_only',
                    ['url' => $urlCode],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }
        }

        $pdf = $renderer->renderMany($products, [
            'website' => $website,
            'productUrls' => $productUrls,
        ]);

        $filename = sprintf('favoris-%s.pdf', date('Y-m-d'));
        $disposition = $request->query->getBoolean('download') ? 'attachment' : 'inline';

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
        ]);
    }
}
