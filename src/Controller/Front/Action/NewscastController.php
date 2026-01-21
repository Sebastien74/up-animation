<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\ActionController;
use App\Entity\Layout\Block;
use App\Entity\Module\Newscast;
use App\Entity\Seo\Url;
use App\Form\Manager\Front\NewscastFiltersInterface;
use App\Form\Type\Module\Newscast\FrontFiltersType;
use App\Model\Core\WebsiteModel;
use App\Model\Module\NewscastModel;
use App\Model\ViewModel;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * NewscastController.
 *
 * Front Newscast renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastController extends ActionController
{
    /**
     * Index.
     *
     * @throws NonUniqueResultException|ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|InvalidArgumentException|QueryException
     */
    #[Route('/action/newscast/index/{url}/{filter}', name: 'front_newscast_index', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        NewscastFiltersInterface $frontFilterManager,
        Url $url,
        ?Block $block = null,
        mixed $filter = null
    ): Response {

        $this->setTemplate('newscast/index.html.twig');
        $this->setModel(NewscastModel::class);
        $this->setModelOptions([]);
        $this->setClassname(Newscast\Newscast::class);
        $this->setListingClassname(Newscast\Listing::class);
        $this->setFiltersForm(FrontFiltersType::class);
        $this->setFiltersFormManager($frontFilterManager);
        $this->setCustomArguments(['thumbConfigurationFirstAsDefault' => true]);
        $this->setInterfaceName('newscast');

        return $this->getIndex($request, $paginator, $url, $block, $filter);
    }

    /**
     * Teaser.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException|QueryException
     */
    #[Route('/action/newscast/index/{block}/{url}/{filter}/{category}', name: 'front_newscast_teaser', options: ['isMainRequest' => false], defaults: ['category' => null], methods: 'GET', schemes: '%protocol%')]
    public function teaser(
        Request $request,
        NewscastFiltersInterface $frontFilterManager,
        Block $block,
        Url $url,
        mixed $filter = null
    ): Response {

        $this->setTemplate('newscast/teaser.html.twig');
        $this->setTeaserClassname(Newscast\Teaser::class);
        $this->setListingClassname(Newscast\Listing::class);
        $this->setClassname(Newscast\Newscast::class);
        $this->setModel(NewscastModel::class);
        $this->setModelOptions([]);
        $this->setFiltersForm(FrontFiltersType::class);
        $this->setFiltersFormManager($frontFilterManager);

        return $this->getTeaser($request, $block, $url, $filter);
    }

    /**
     * View.
     *
     * @throws ContainerExceptionInterface|InvalidArgumentException|NonUniqueResultException|NotFoundExceptionInterface|\ReflectionException|MappingException|\Exception
     */
    #[Route([
        'fr' => '/{pageUrl}/fiche-actualite/{url}',
        'en' => '/{pageUrl}/news-card/{url}',
    ], name: 'front_newscast_view', methods: 'GET', schemes: '%protocol%', priority: 300)]
    #[Route([
        'fr' => '/fiche-actualite/{url}',
        'en' => '/news-card/{url}',
    ], name: 'front_newscast_view_only', methods: 'GET', schemes: '%protocol%', priority: 300)]
    #[Cache(expires: 'tomorrow', public: true)]
    public function view(
        Request $request,
        string $url,
        ?string $pageUrl = null,
        bool $preview = false): Response
    {
        $this->setClassname(Newscast\Newscast::class);
        $this->setListingClassname(Newscast\Listing::class);
        $this->setCategoryClassname(Newscast\Category::class);
        $this->setModel(NewscastModel::class);
        $this->setModelOptions([]);
        $this->setInterfaceName('newscast');
        $this->setAssociatedThumbMethod('category');
        $this->setAssociatedEntitiesProperties(['category']);
        $this->setAssociatedEntitiesLimit(6);
        $this->setAssociatedEntitiesLastDate(9999999);

        $mainCategory = $this->getDefaultCategory($this->getWebsite());
        if ($mainCategory) {
            $this->setCustomArguments(['mainTemplateCategory' => $mainCategory, 'mainLayout' => $mainCategory->layout]);
        }

        return $this->getView($request, $url, $pageUrl, $preview);
    }

    /**
     * Preview.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|NonUniqueResultException|InvalidArgumentException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin-%security_token%/front/newscast/preview/{url}', name: 'front_newscast_preview', methods: 'GET|POST', schemes: '%protocol%')]
    public function preview(Request $request, Url $url): Response
    {
        $this->setClassname(Newscast\Newscast::class);
        $this->setModel(NewscastModel::class);
        $this->setModelOptions([]);
        $this->setListingClassname(Newscast\Listing::class);
        $this->setCategoryClassname(Newscast\Category::class);
        $this->setController(NewscastController::class);

        return $this->getPreview($request, $url);
    }

    /**
     * Get default Category.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private function getDefaultCategory(WebsiteModel $website): ?ViewModel
    {
        $category = $this->coreLocator->em()->getRepository(Newscast\Category::class)->findOneBy([
            'website' => $website->entity,
            'asDefault' => true,
        ]);
        return $category ? ViewModel::fromEntity($category, $this->coreLocator) : null;
    }
}
