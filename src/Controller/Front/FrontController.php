<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Media\ThumbConfiguration;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Entity\Seo\Url;
use App\Http\TransparentPixelResponse;
use App\Model\Core\WebsiteModel;
use App\Model\MediaModel;
use App\Repository\Core\WebsiteRepository;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\FrontLocatorInterface;
use App\Twig\Content\ThumbnailRuntime;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * FrontController.
 *
 * Front base controller
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontController extends CacheController
{
    public function __construct(
        protected FrontLocatorInterface $frontLocator,
        protected CoreLocatorInterface $coreLocator,
    ) {
        parent::__construct($coreLocator);
    }

    protected ?string $logDir = null;

    /**
     * Media loader.
     *
     * @throws LoaderError|MappingException|NonUniqueResultException|RuntimeError|SyntaxError
     */
    #[Route('/front/media/loader', name: 'front_media_loader', methods: 'GET', schemes: '%protocol%')]
    public function mediaLoader(ThumbnailRuntime $runtime): JsonResponse
    {
        $path = !empty($_GET['_path']) ? $_GET['_path'] : '';
        $decodedString = urldecode($path);
        parse_str($decodedString, $parameters);
        $options = array_filter($parameters, function ($key) {
            return !str_contains($key, '_');
        }, ARRAY_FILTER_USE_KEY);
        foreach ($options as $key => $value) {
            if (is_numeric($value)) {
                $options[$key] = (int) $value;
            }
        }
        $options['lazyLoad'] = isset($options['lazyLoad']) && $options['lazyLoad'];
        $entity = $this->coreLocator->em()->getRepository($options['classname'])->find($options['id']);
        $thumbs = !empty($options['thumbConfigurationJson']) ? (array) json_decode($options['thumbConfigurationJson']) : [];
        $options['thumbConfiguration'] = [];
        foreach ($thumbs as $screen => $thumbId) {
            $options['thumbConfiguration'][$screen] = $this->coreLocator->em()->getRepository(ThumbConfiguration::class)->find($thumbId);
        }
        $options['class'] = !empty($options['class']) ? $options['class'].' in-viewport' : 'in-viewport';
        $media = MediaModel::fromEntity($entity, $this->coreLocator);
        $arguments = $runtime->thumb($media, $options['thumbConfiguration'], array_merge($options, ['only_arguments' => true, 'lazyFiles' => true, 'forceThumb' => true, 'inAdmin' => $options['inAdmin']]));

        if (isset($options['path']) && $options['path']) {
            return new JsonResponse(['success' => true]);
        }

        $filesystem = new Filesystem();
        $dirname = $this->coreLocator->cacheDir();
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $finder = Finder::create();
        $finder->in($dirname)->name('pools-cache-*')->depth([0]);
        foreach ($finder as $file) {
            if ($filesystem->exists($file->getRealPath())) {
                $filesystem->remove($file->getRealPath());
            }
        }

        return new JsonResponse(['html' => $this->renderView('core/image-config.html.twig', $arguments)]);
    }

    /**
     * Maintenance page.
     */
    #[Route('/temporary-page/in-build', name: 'front_build_page', methods: 'GET', schemes: '%protocol%')]
    public function buildPage(): Response
    {
        $website = $this->coreLocator->website();
        $configuration = $website->configuration;
        $websiteTemplate = $configuration->template;
        return $this->render('front/'.$websiteTemplate.'/template/build.html.twig', [
            'website' => $website,
            'logos' => $website->logos,
            'configuration' => $configuration,
            'templateName' => 'build',
        ]);
    }

    /**
     * To detect activity.
     */
    #[Route('/front/activity', name: 'front_activity', options: ['expose' => true, 'isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function activity(): JsonResponse
    {
        return new JsonResponse(['success' => true], 200);
    }

    /**
     * To download a file.
     */
    #[Route('/front/download/{website}/{filename}', name: 'front_download_file', options: ['expose' => true, 'isMainRequest' => false], defaults: ['filename' => null], methods: 'GET', schemes: '%protocol%')]
    public function download(Website $website, ?string $filename): BinaryFileResponse|Response
    {
        if ($filename) {
            $fileDirname = $this->coreLocator->projectDir().'/public/uploads/'.$website->getUploadDirname().'/'.$filename;
            $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);
            $filesystem = new Filesystem();
            if ($filesystem->exists($fileDirname)) {
                $response = new BinaryFileResponse($fileDirname);
                return $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    basename($fileDirname)  // Set the desired file name seen by the user
                );
            }
        }

        return new Response();
    }

    /**
     * To set website alert user session.
     */
    #[Route('/front/website-alert/{mode}', name: 'website_alert', options: ['expose' => true, 'isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function websiteAlert(Request $request, string $mode): JsonResponse
    {
        $hide = 'show' === $request->get('currentStatus');
        $request->getSession()->set('front_website_alert_'.$mode, $hide);

        return new JsonResponse(['success' => true, 'hide' => $hide], 200);
    }

    /**
     * Track emails sends.
     */
    #[Route('/front/emails/resources/pixel.gif', name: 'front_track_email', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function trackEmails(Request $request, string $logDir, EventDispatcherInterface $eventDispatcher): TransparentPixelResponse
    {
        $id = $request->query->get('code');
        $this->logDir = $logDir;
        $classname = $request->query->get('classname') ? urldecode($request->query->get('classname')) : null;
        if (null !== $id && null !== $classname) {
            $eventDispatcher->addListener(KernelEvents::TERMINATE,
                function (KernelEvent $event) {
                    $logger = new Logger('form.emails.tracks');
                    $logger->pushHandler(new RotatingFileHandler($this->logDir.'/emails-tracks.log', 10, Level::Info));
                    $logger->info('Message ouvert.');
                }
            );
        }

        return new TransparentPixelResponse();
    }

    /**
     * Show WebsiteModel.
     *
     * @throws \Exception
     */
    #[Route('/front/website/selector/{website}', name: 'front_website_selector', options: ['isMainRequest' => false], methods: 'GET', schemes: '%protocol%')]
    public function websitesSelector(WebsiteRepository $websiteRepository, Website $website): Response
    {
        return $this->render('front/'.$website->getConfiguration()->getTemplate().'/include/websites-selector.html.twig', [
            'currentWebsite' => $website,
            'websites' => $websiteRepository->findAll(),
        ]);
    }

    /**
     * To get default arguments.
     */
    protected function defaultArgs(WebsiteModel $website, ?Url $url = null, mixed $entityModel = null): array
    {
        $user = $this->getUser();
        $thumbConfigurationHeader = $this->thumbConfiguration($website, Block::class, null, 'title-header');
        $websiteTemplate = $website->configuration->template;

        $arguments = [
            'isUserBack' => $this->coreLocator->checkIP($website) && !$user instanceof UserFront || $user instanceof User,
            'website' => $website,
            'configuration' => $website->configuration,
            'websiteTemplate' => $websiteTemplate,
            'mainMenus' => $this->frontLocator->menuService()->all($website, $url),
            'mainPages' => $website->configuration->pages,
            'logos' => $website->configuration->logos,
            'thumbConfigurationHeader' => $thumbConfigurationHeader,
            'url' => $url,
            'preloadFiles' => is_object($entityModel) && property_exists($entityModel, 'preloadFiles') ? $entityModel->preloadFiles : false,
        ];

        if (str_contains($this->coreLocator->request()->get('_route'), '_security')
            || str_contains($this->coreLocator->request()->get('_route'), 'security_')) {
            $arguments['templateName'] = 'security';
        }

        return $arguments;
    }
}
