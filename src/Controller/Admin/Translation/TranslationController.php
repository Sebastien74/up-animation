<?php

declare(strict_types=1);

namespace App\Controller\Admin\Translation;

use App\Controller\Admin\AdminController;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationUnit;
use App\Form\Interface\IntlFormManagerInterface;
use App\Form\Type\Translation\AddTranslationType;
use App\Repository\Core\WebsiteRepository;
use App\Repository\Translation\TranslationDomainRepository;
use App\Service\Development\EntityService;
use App\Service\Interface\AdminLocatorInterface;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Translation\Extractor;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Yaml\Yaml;

/**
 * TranslationController.
 *
 * Translation management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/{website}/translations', schemes: '%protocol%')]
class TranslationController extends AdminController
{
    /**
     * TranslationController constructor.
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
     * New Translation.
     */
    #[Route('/new', name: 'admin_translation_new', methods: 'GET|POST')]
    public function newTranslation(Request $request): JsonResponse|string|Response
    {
        $form = $this->createForm(AddTranslationType::class, null, [
            'action' => $this->generateUrl('admin_translation_new', ['website' => $this->getWebsite()->id]),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->intlFormInterface->unit()->addUnit($form, $this->getWebsite()->entity);

            return new JsonResponse(['success' => true], Response::HTTP_OK);
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            return new JsonResponse(['html' => $this->renderView('admin/core/new.html.twig', ['form' => $form->createView()])], Response::HTTP_OK);
        }

        return $this->adminRender('admin/core/new.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Search edit Translations.
     */
    #[Route('/search-edit', name: 'admin_translation_search_edit', methods: 'GET')]
    public function searchEdit(Request $request, TranslationDomainRepository $domainRepository): string|Response
    {
        return $this->adminRender('admin/page/translation/translations.html.twig', [
            'domains' => $domainRepository->findBySearch($request->query->get('search')),
        ]);
    }

    /**
     * Data fixtures yaml parser.
     */
    #[Route('/data-fixtures-parser', name: 'admin_translation_data_fixtures_parser', methods: 'GET')]
    public function dataFixturesParser(Request $request, string $projectDir): RedirectResponse
    {
        $filesystem = new Filesystem();
        $fixturesDirname = $projectDir.'/bin/data/translations/';
        $fixturesDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fixturesDirname);
        $parserDirname = $fixturesDirname.'parser';

        if ($filesystem->exists($parserDirname)) {
            $finder = Finder::create();
            $finder->in($parserDirname)->name('*.yaml')->name('*.yml');
            foreach ($finder as $file) {
                if ('file' === $file->getType() && !str_contains($file->getFilename(), 'undefined')) {
                    $matches = explode('.', str_replace(['.yaml', '.yml'], '', $file->getFilename()));
                    $locale = end($matches);
                    $translationFileName = $fixturesDirname.'translations.'.$locale.'.yaml';
                    if (str_contains($file->getFilename(), 'entity_')) {
                        $translationFileName = $file->getFilename() !== 'entity_+intl-icu.'.$locale.'.yaml' ? $fixturesDirname.str_replace(['+intl-icu', '.yml'], ['', '.yaml'], $file->getFilename()) : null;
                    }
                    if ($translationFileName) {
                        $newValues = Yaml::parseFile($file->getRealPath());
                        $existingValues = $filesystem->exists($translationFileName) ? Yaml::parseFile($translationFileName) : [];
                        if (is_array($newValues)) {
                            foreach ($newValues as $keyName => $value) {
                                $keyName = str_replace('__', '', $keyName);
                                if (empty($existingValues[$keyName]) && $value && $value !== $keyName && !is_numeric($value)
                                    && !str_contains($keyName, 'url-') && !str_contains($keyName, 'http') && !str_contains($value, 'http')) {
                                    $existingValues[$keyName] = str_replace('__', '', $value);
                                }
                            }
                        }
                        ksort($existingValues);
                        $yaml = Yaml::dump($existingValues);
                        file_put_contents($translationFileName, $yaml);
                    }
                }
            }
        }

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * Extract translations.
     *
     * @throws Exception
     */
    #[Route('/extract/{locale}', name: 'admin_translation_extract', options: ['expose' => true], methods: 'GET')]
    public function extract(Request $request, WebsiteRepository $websiteRepository, EntityService $entityService, Extractor $extractor, string $locale): JsonResponse
    {
        $website = $websiteRepository->find($request->attributes->get('website'));
        $configuration = $website->getConfiguration();
        if ($locale === $configuration->getLocale()) {
            $extractor->extractEntities($website, $configuration->getLocale(), $configuration->getAllLocales());
        }

        if (!$request->query->get('skip_extract')) {
            $extractor->extract($locale);
        }

        $entityService->execute($website, $locale);

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    /**
     * Translations progress.
     */
    #[Route('/progress', name: 'admin_translation_progress', options: ['expose' => true], methods: 'GET')]
    public function progress(Request $request, WebsiteRepository $websiteRepository, TranslationDomainRepository $domainRepository, Extractor $extractor): JsonResponse
    {
        $domainName = $request->query->get('domain');
        $domains = 'only_front' === $domainName ? $domainRepository->getFrontDomains() : [];
        $domainName = 'only_front' === $domainName ? null : $domainName;
        $website = $websiteRepository->find($request->attributes->get('website'));
        $locales = $website->getConfiguration()->getAllLocales();

        $yaml = $extractor->findYaml($locales);
        if (!empty($domainName) && !empty($yaml[$domainName])) {
            $extractor->domain($domainName);
            $translations[$domainName] = $yaml[$domainName];
        } else {
            $translations = $yaml;
            foreach ($translations as $name => $items) {
                if ($name) {
                    $extractor->domain($name);
                    if ($domains && !in_array($name, $domains)) {
                        unset($translations[$name]);
                    }
                }
            }
        }

        return new JsonResponse(['html' => $this->renderView('admin/page/translation/progress.html.twig', [
            'translations' => $translations,
            'domainName' => $request->query->get('domain'),
            'multiCols' => count($translations) > 1,
        ])], Response::HTTP_OK);
    }

    /**
     * Generate translation.
     */
    #[Route('/generate-batch/{locale}/{domain}', name: 'admin_translation_generate_batch', options: ['expose' => true], methods: 'GET|POST')]
    public function batchGenerate(
        Request $request,
        Extractor $extractor,
        WebsiteRepository $websiteRepository,
        string $locale,
        string $domain): JsonResponse
    {
        $website = $websiteRepository->find($request->attributes->get('website'));
        $defaultLocale = $website->getConfiguration()->getLocale();
        $translations = json_decode($request->getContent(), true)['translations'] ?? [];

        if (empty($translations) && $request->isMethod('GET')) {
            return new JsonResponse(['success' => true, 'message' => 'Empty translations for GET request'], Response::HTTP_OK);
        }

        $count = 0;
        $entityManager = $this->coreLocator->em();
        foreach ($translations as $item) {
            $extractor->generateTranslation(
                $defaultLocale,
                $locale,
                urldecode($domain),
                $item['content'] ?? null,
                isset($item['keyName']) ? (string) $item['keyName'] : null
            );
            $count++;
            if ($count % 50 === 0) {
                $entityManager->flush();
                $entityManager->clear(Translation::class);
                $entityManager->clear(TranslationUnit::class);
            }
        }

        $entityManager->flush();

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    /**
     * Generate translation.
     */
    #[Route('/generate/{locale}/{domain}', name: 'admin_translation_generate', options: ['expose' => true], methods: 'GET')]
    public function generate(
        Request $request,
        Extractor $extractor,
        WebsiteRepository $websiteRepository,
        string $locale,
        string $domain): JsonResponse
    {
        $website = $websiteRepository->find($request->attributes->get('website'));
        $defaultLocale = $website->getConfiguration()->getLocale();
        $keyName = $request->query->get('keyName');
        $extractor->generateTranslation(
            $defaultLocale,
            $locale,
            urldecode($domain),
            urldecode($request->query->get('content')),
            $keyName !== null ? (string) urldecode($keyName) : null
        );

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    /**
     * Generate translations file.
     */
    #[Route('/generate/files', name: 'admin_translation_generate_files', options: ['expose' => true], methods: 'GET')]
    public function generateFiles(Request $request, WebsiteRepository $websiteRepository, Extractor $extractor): JsonResponse
    {
        $website = $websiteRepository->find($request->attributes->get('website'));
        $extractor->initFiles($website->getConfiguration()->getAllLocales());

        return new JsonResponse(['success' => true], Response::HTTP_OK);
    }

    /**
     * Clear cache.
     */
    #[Route('/cache-clear', name: 'admin_translation_cache_clear', options: ['expose' => true], methods: 'GET')]
    public function cacheClear(Request $request, Extractor $extractor, string $cacheDir): RedirectResponse|JsonResponse
    {
        $extractor->clearCache();
        if ($request->query->get('ajax')) {
            return new JsonResponse(['success' => true], Response::HTTP_OK);
        }
        $request->getSession()->getFlashBag()->add('command', [
            'dirname' => $cacheDir.'/translations',
            'command' => 'Filesystem remove',
            'output' => $this->coreLocator->translator()->trans('Le cache des traductions a été supprimé !!', [], 'admin'),
        ]);

        return $this->redirect($request->headers->get('referer'));
    }
}
