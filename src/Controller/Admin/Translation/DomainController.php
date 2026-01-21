<?php

declare(strict_types=1);

namespace App\Controller\Admin\Translation;

use App\Controller\Admin\AdminController;
use App\Entity\Translation\TranslationDomain;
use App\Entity\Translation\TranslationUnit;
use App\Form\Type\Translation\DomainType;
use App\Repository\Translation\TranslationDomainRepository;
use App\Repository\Translation\TranslationUnitRepository;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DomainController.
 *
 * Translation DomainModel management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_TRANSLATION')]
#[Route('/admin-%security_token%/{website}/translations/domains', schemes: '%protocol%')]
class DomainController extends AdminController
{
    protected ?string $class = TranslationDomain::class;
    protected ?string $formType = DomainType::class;

    /**
     * Index TranslationDomain.
     *
     * {@inheritdoc}
     */
    #[Route('/index/{domains}', name: 'admin_translationdomain_index', options: ['expose' => true], defaults: ['domains' => null], methods: 'GET')]
    public function index(Request $request, PaginatorInterface $paginator, ?string $domains = null)
    {
        $this->template = 'admin/page/translation/domains.html.twig';
        $this->arguments['domains'] = $this->entities = !$domains
            ? $this->coreLocator->em()->getRepository(TranslationDomain::class)->findFront()
            : $this->coreLocator->em()->getRepository(TranslationDomain::class)->findAdmin();
        $this->forceEntities = true;

        return parent::index($request, $paginator);
    }

    /**
     * Edit TranslationDomain.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/edit/{translationdomain}', name: 'admin_translationdomain_edit', methods: 'GET|POST')]
    public function edit(Request $request)
    {
        return parent::edit($request);
    }

    /**
     * Edit DomainModel.
     *
     * @throws NonUniqueResultException
     */
    #[Route('/edit/domain/{translationdomain}', name: 'admin_translationsdomain_edit', methods: 'GET')]
    public function translationsDomain(Request $request, TranslationDomainRepository $domainRepository, TranslationUnitRepository $unitRepository)
    {
        $indexHelper = $this->adminLocator->indexHelper();
        $interface = $this->getInterface(TranslationUnit::class);
        $indexHelper->setDisplaySearchForm(true);

        $domain = $domainRepository->findDomain($request->attributes->getInt('translationdomain'));
        $indexHelper->execute(TranslationUnit::class, $interface, 15, $unitRepository->findBy(['domain' => $domain]), true);
        $template = 'admin/page/translation/domain.html.twig';

        parent::breadcrumb($request, [
            $this->coreLocator->translator()->trans('Groupes de traductions', [], 'admin_breadcrumb') => 'admin_translationdomain_index'
        ]);

        $arguments = array_merge($this->arguments, [
            'domain' => $domain,
            'searchForm' => $indexHelper->getSearchForm()->createView(),
            'pagination' => $indexHelper->getPagination(),
        ]);

        if (!empty($request->get('ajax'))) {
            return new JsonResponse(['html' => $this->adminRender($template, $arguments, $request)]);
        }

        return $this->adminRender($template, $arguments);
    }

    /**
     * Delete TranslationDomain.
     *
     * {@inheritdoc}
     */
    #[IsGranted('ROLE_INTERNAL')]
    #[Route('/delete/{translationdomain}', name: 'admin_translationsdomain_delete', methods: 'DELETE')]
    public function deleteDomain(Request $request, string $projectDir)
    {
        /** @var TranslationDomain $domain */
        $domain = $this->coreLocator->em()->getRepository(TranslationDomain::class)->find($request->get('translationdomain'));

        if (!$domain) {
            return new JsonResponse(['success' => false]);
        }

        $website = $this->getWebsite();
        $name = $domain->getName();

        foreach ($website->configuration->allLocales as $locale) {
            $finder = Finder::create();
            $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, '/translations/');
            $finder->files()->in($projectDir.$dirname)->name($name.'+intl-icu.'.$locale.'.yaml');
            foreach ($finder as $file) {
                $filesystem = new Filesystem();
                $filesystem->remove($file->getPathname());
            }
        }

        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {
        if ($request->get('translationdomain')) {
            $items[$this->coreLocator->translator()->trans('Groupes de traductions', [], 'admin_breadcrumb')] = 'admin_translationdomain_index';
        }

        parent::breadcrumb($request, $items);
    }
}
