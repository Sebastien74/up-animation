<?php

declare(strict_types=1);

namespace App\Controller\Admin\Translation;

use App\Controller\Admin\AdminController;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationUnit;
use App\Form\Interface\IntlFormManagerInterface;
use App\Form\Type\Translation\UnitType;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

/**
 * UnitController.
 *
 * Translation Unit management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/translations/units', schemes: '%protocol%')]
class UnitController extends AdminController
{
    protected ?string $class = TranslationUnit::class;
    protected ?string $formType = UnitType::class;

    /**
     * UnitController constructor.
     */
    public function __construct(
        protected IntlFormManagerInterface $intlFormInterface,
        protected CoreLocatorInterface $coreLocator,
        protected AdminLocatorInterface $adminLocator,
    ) {
        $this->formManager = $intlFormInterface->unit();
        parent::__construct($coreLocator, $adminLocator);
    }

    /**
     * Edit TranslationUnit.
     *
     * {@inheritdoc}
     */
    #[Route('/edit/{translationunit}/{displayDomain}', name: 'admin_translationunit_edit', defaults: ['displayDomain' => null], methods: 'GET|POST')]
    public function edit(Request $request)
    {
        $this->entity = $this->coreLocator->em()->getRepository(TranslationUnit::class)->find($request->get('translationunit'));
        if (!$this->entity) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette clé n'existe pas !!", [], 'admin'));
        }

        $this->template = 'admin/page/translation/unit.html.twig';
        $this->arguments['displayDomain'] = $request->get('displayDomain');

        return parent::edit($request);
    }

    /**
     * Regenerate TranslationUnit.
     */
    #[Route('/regenerate/{translationUnit}', name: 'admin_translationunit_regenerate', methods: 'GET')]
    public function regenerate(Request $request, TranslationUnit $translationUnit)
    {
        $website = $this->getWebsite();

        foreach ($website->configuration->allLocales as $locale) {
            $translation = $this->coreLocator->em()->getRepository(Translation::class)->findOneBy([
                'unit' => $translationUnit,
                'locale' => $locale,
            ]);

            if (!$translation) {
                $translation = new Translation();
                $translation->setLocale($locale);
                $translation->setUnit($translationUnit);

                $this->coreLocator->em()->persist($translation);
                $this->coreLocator->em()->flush();
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Delete TranslationUnit.
     *
     * {@inheritdoc}
     */
    #[Route('/delete/{translationunit}', name: 'admin_translationunit_delete', methods: 'DELETE')]
    public function deleteUnit(Request $request, string $projectDir)
    {
        /** @var TranslationUnit $unit */
        $unit = $this->coreLocator->em()->getRepository(TranslationUnit::class)->find($request->get('translationunit'));

        if (!$unit) {
            return new JsonResponse(['success' => false]);
        }

        $website = $this->getWebsite();
        $domainName = $unit->getDomain()->getName();

        foreach ($website->configuration->allLocales as $locale) {
            $finder = Finder::create();
            $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, '/translations/');
            $finder->files()->in($projectDir.$dirname)->name($domainName.'+intl-icu.'.$locale.'.yaml');
            foreach ($finder as $file) {
                $values = Yaml::parseFile($file->getPathname());
                if (is_array($values) && !empty($values[$unit->getKeyname()])) {
                    unset($values[$unit->getKeyname()]);
                    $yaml = Yaml::dump($values);
                    file_put_contents($file->getPathname(), $yaml);
                }
            }
        }

        return parent::delete($request);
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(Request $request, array $items = []): void
    {

        $items[$this->coreLocator->translator()->trans('Groupes de traductions', [], 'admin_breadcrumb')] = 'admin_translationdomain_index';

        parent::breadcrumb($request, $items);
    }
}
