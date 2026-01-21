<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\ActionController;
use App\Entity\Layout\Block;
use App\Entity\Module\Portfolio\Card;
use App\Entity\Module\Recruitment\Job;
use App\Entity\Module\Recruitment\Listing;
use App\Entity\Seo\Url;
use App\Form\Manager\Front\JobFiltersInterface;
use App\Form\Type\Module\Recruitment\FrontFiltersType;
use App\Model\Module\JobModel;
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
use Symfony\Component\Routing\Attribute\Route;;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * RecruitmentController.
 *
 * Front Recruitment renders
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class RecruitmentController extends ActionController
{
    /**
     * Index.
     *
     * @throws ContainerExceptionInterface|NonUniqueResultException|NotFoundExceptionInterface|MappingException|InvalidArgumentException|QueryException
     */
    #[Route('/action/recruitment/index/{url}/{filter}', name: 'front_recruitmentjob_index', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        JobFiltersInterface $frontFilterManager,
        Url $url,
        ?Block $block = null,
        mixed $filter = null): Response
    {
        $this->setTemplate('recruitment/index.html.twig');
        $this->setModel(JobModel::class);
        $this->setClassname(Job::class);
        $this->setListingClassname(Listing::class);
        $this->setFiltersForm(FrontFiltersType::class);
        $this->setFiltersFormManager($frontFilterManager);
        $this->setCustomArguments([]);

        return $this->getIndex($request, $paginator, $url, $block, $filter);
    }

    /**
     * Teaser.
     */
    public function teaser(Request $request, Url $url, ?Block $block = null): array|Response
    {
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $template = $configuration->template;

        return $this->render('front/'.$template.'/actions/recruitment/teaser.html.twig', [
            'website' => $website,
            'url' => $url,
            'block' => $block,
            'websiteTemplate' => $template,
        ]);
    }

    /**
     * View.
     *
     * @throws \ReflectionException|ContainerExceptionInterface|InvalidArgumentException|NonUniqueResultException|NotFoundExceptionInterface|MappingException|QueryException
     */
    #[Route([
        'fr' => '/{pageUrl}/offre/{url}',
        'en' => '/{pageUrl}/offer/{url}',
    ], name: 'front_recruitmentjob_view', methods: 'GET', schemes: '%protocol%')]
    #[Route([
        'fr' => '/offre/{url}',
        'en' => '/offer/{url}',
    ], name: 'front_recruitmentjob_view_only', methods: 'GET', schemes: '%protocol%')]
    #[Cache(expires: 'tomorrow', public: true)]
    public function view(
        Request $request,
        string $url,
        ?string $pageUrl = null,
        bool $preview = false): array|Response
    {
        $this->setTemplate('recruitment/view.html.twig');
        $this->setModel(JobModel::class);
        $this->setClassname(Job::class);
        $this->setListingClassname(Listing::class);

        return $this->getView($request, $url, $pageUrl, $preview);
    }

    /**
     * Preview.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|MappingException|NonUniqueResultException|InvalidArgumentException
     */
    #[IsGranted('ROLE_ADMIN')]
    #[\Symfony\Component\Routing\Attribute\Route('/admin-%security_token%/front/recruitment/preview/{url}', name: 'front_recruitmentjob_preview', methods: 'GET|POST', schemes: '%protocol%')]
    public function preview(Request $request, Url $url): Response
    {
        $this->setClassname(Job::class);
        $this->setModel(JobModel::class);
        $this->setModelOptions([]);
        $this->setListingClassname(Listing::class);
        $this->setController(RecruitmentController::class);

        return $this->getPreview($request, $url);
    }
}
