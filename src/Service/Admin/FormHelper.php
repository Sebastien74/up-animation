<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Layout\Zone;
use App\Entity\Media\Media;
use App\Form\Manager\Core\GlobalManager;
use App\Form\Manager\Media\MediaManager;
use App\Form\Manager\Translation\IntlManager;
use App\Form\Type\Core\DefaultType;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Core\AppRuntime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\Mapping\MappingException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * FormHelper.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FormHelper::class, 'key' => 'form_helper'],
])]
class FormHelper
{
    private ?Request $request;
    private ?Request $currentRequest;
    private bool $disableFlash = false;
    private array $interface = [];
    private Website $website;
    private ?object $entity = null;
    private ?FormInterface $form = null;
    private ?string $redirection = null;
    private array $haveH1 = [];

    /**
     * FormHelper constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FormFactoryInterface $formFactory,
        private readonly GlobalManager $globalManager,
        private readonly MediaManager $mediaManager,
        private readonly IntlManager $intlManager,
        private readonly AppRuntime $appExtension,
    ) {
        $this->setRequest();
    }

    /**
     * To set request.
     */
    private function setRequest(): void
    {
        $this->request = $this->coreLocator->requestStack()->getMainRequest();
        $this->currentRequest = $this->coreLocator->requestStack()->getCurrentRequest();
    }

    /**
     * Execute FormHelper.
     *
     * @throws ContainerExceptionInterface|MappingException|NonUniqueResultException|NotFoundExceptionInterface|\ReflectionException|InvalidArgumentException
     */
    public function execute(
        ?string $formType = null,
        mixed $entity = null,
        ?string $classname = null,
        array $options = [],
        mixed $formManager = null,
        bool $disableFlash = false,
        ?string $view = null): void
    {
        $this->disableFlash = $disableFlash;
        if ($classname) {
            $this->setInterface($classname);
            $this->setWebsite();
            $this->setEntity($entity, $classname, $view);
            $this->setUrls($entity);
            $this->checkH1();
            if ('new' != $view && !$this->request->isMethod('post')) {
                $this->setIntls();
                if (!$entity instanceof Configuration) {
                    $this->setMediaRelations();
                    $this->setMediaRelation();
                }
                if ($this->entity instanceof Media) {
                    $this->setMediaScreen($this->entity);
                }
            }
            $this->setForm($formType, $options);
            $this->submit($formManager);
        }
    }

    /**
     * Set Interface.
     *
     * @throws NonUniqueResultException
     */
    public function setInterface(string $classname): void
    {
        $this->interface = $this->coreLocator->interfaceHelper()->generate($classname);
    }

    /**
     * Set WebsiteModel.
     */
    public function setWebsite(): void
    {
        $websiteRequest = $this->request->get('website')
            ? $this->request->get('website')
            : $this->request->get('site');
        $this->website = $this->coreLocator->em()->getRepository(Website::class)->find($websiteRequest);
    }

    /**
     * Set Entity.
     *
     * @throws NonUniqueResultException
     */
    public function setEntity(mixed $entity = null, ?string $classname = null, ?string $view = null): void
    {
        $entityRequest = null;
        if ($this->interface['name']) {
            $entityRequest = $this->request->get($this->interface['name'])
                ? $this->request->get($this->interface['name'])
                : $this->currentRequest->get($this->interface['name']);
        }

        if ($entity) {
            $this->entity = $entity;
        } elseif ('new' === $view) {
            $this->entity = new $classname();
        } elseif ('layout' === $view) {
            $this->entity = $this->getLayout($classname, intval($entityRequest));
        } else {
            $this->entity = $entityRequest && !is_array($entityRequest)
                ? $this->coreLocator->em()->getRepository($classname)->find($entityRequest)
                : $this->interface['entity'];
        }

        $this->setMasterField();

        if ($this->entity && property_exists($this->entity, 'locale') && !$this->entity->getLocale()) {
            $locale = $this->request->get('entitylocale') ? $this->request->get('entitylocale') : $this->website->getConfiguration()->getLocale();
            $this->entity->setLocale($locale);
        }

        if (!$this->entity) {
            throw new NotFoundHttpException($this->coreLocator->translator()->trans('Aucune entité trouvée.', [], 'admin'));
        }
    }

    /**
     * Set Urls.
     */
    public function setUrls(mixed $entity): void
    {
        if ($entity && method_exists($entity, 'getUrls')) {
            $locales = $this->website->getConfiguration()->getAllLocales();
            $clear = $entity->getUrls()->count() > count($locales);
            if ($clear) {
                $onlineUrls = [];
                foreach ($entity->getUrls() as $url) {
                    if ($url->isOnline()) {
                        $onlineUrls[] = $url->getId();
                    }
                }
                foreach ($entity->getUrls() as $url) {
                    if (!in_array($url->getId(), $onlineUrls) && 'error' === $url->getCode()) {
                        $entity->removeUrl($url);
                    }
                }
            }
        }
    }

    /**
     * Check if H1 existing.
     */
    private function checkH1(): void
    {
        if ($this->entity instanceof Page && $this->entity->getId()
            || method_exists($this->entity, 'isCustomLayout') && $this->entity->isCustomLayout() && $this->entity->getId()) {
            $result = [];
            $locales = $this->website->getConfiguration()->getAllLocales();
            foreach ($locales as $locale) {
                $existing = $this->coreLocator->em()->getRepository(Block::class)->findTitleByForceAndLocalePage($this->entity, $locale, 1, true);
                $result[$locale] = $existing ? count($existing) : 0;
                if (0 === $result[$locale] || $result[$locale] > 1) {
                    $result['error'] = true;
                }
            }
            $this->haveH1 = $result;
        }
    }

    /**
     * Have H1.
     */
    public function haveH1(): array
    {
        return $this->haveH1;
    }

    /**
     * Get Layout.
     *
     * @throws NonUniqueResultException
     */
    private function getLayout(string $classname, int $entityId): mixed
    {
        $referEntity = new $classname();

        $queryBuilder = $this->coreLocator->em()->createQueryBuilder()->select('e')
            ->from($classname, 'e')
            ->leftJoin('e.layout', 'l')
            ->leftJoin('l.zones', 'z')
            ->leftJoin('z.cols', 'c')
            ->leftJoin('c.blocks', 'b')
            ->leftJoin('b.action', 'ba')
            ->leftJoin('b.actionIntls', 'bai')
            ->leftJoin('b.intls', 'bi')
            ->leftJoin('b.blockType', 'bbt')
            ->andWhere('e.id = :id')
            ->setParameter('id', $entityId)
            ->addSelect('l')
            ->addSelect('z')
            ->addSelect('c')
            ->addSelect('b')
            ->addSelect('ba')
            ->addSelect('bai')
            ->addSelect('bi')
            ->addSelect('bbt');

        if (method_exists($referEntity, 'getUrls')) {
            $queryBuilder->leftJoin('e.urls', 'u')
                ->leftJoin('u.seo', 's')
                ->addSelect('u')
                ->addSelect('s');
        }

        return $queryBuilder->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Set Entity MasterField.
     */
    private function setMasterField(): void
    {
        if ($this->entity && !empty($this->interface['masterField']) && property_exists($this->entity, $this->interface['masterField'])) {
            $masterEntity = null;
            if ('configuration' === $this->interface['masterField']) {
                $masterEntity = $this->website->getConfiguration();
            } else {
                $metadata = $this->coreLocator->em()->getClassMetadata($this->interface['classname']);
                $masterClassname = $metadata->associationMappings[$this->interface['masterField']]['targetEntity'];
                if (!empty($this->request->get($this->interface['masterField']))) {
                    $masterEntity = $this->coreLocator->em()->getRepository($masterClassname)->find($this->interface['masterFieldId']);
                }
            }
            if (!empty($masterEntity)) {
                $setter = 'set'.ucfirst($this->interface['masterField']);
                $this->entity->$setter($masterEntity);
            }
        }
    }

    /**
     * Synchronize locale MediaRelation.
     */
    private function setMediaRelation(): void
    {
        if (method_exists($this->entity, 'getMediaRelation')) {
            $this->mediaManager->setEntityLocale($this->interface, $this->entity, $this->website);
        }
    }

    /**
     * Synchronize locales MediaRelation[].
     *
     * @throws NonUniqueResultException
     */
    private function setMediaRelations(): void
    {
        if (method_exists($this->entity, 'getMediaRelations') && !$this->entity instanceof Media) {
            $this->mediaManager->setMediaRelations($this->entity, $this->website, $this->interface);
        }
    }

    /**
     * Synchronize locale intl[].
     */
    private function setIntls(): void
    {
        $this->intlManager->synchronizeLocales($this->entity, $this->website);
    }

    /**
     * Synchronize locale Media screens.
     */
    private function setMediaScreen(Media $media): void
    {
        $this->mediaManager->synchronizeScreens($media);
    }

    /**
     * Get Entity.
     */
    public function getEntity(): ?object
    {
        return $this->entity;
    }

    /**
     * Get Form.
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * Set Form.
     *
     * @throws MappingException|\ReflectionException
     */
    public function setForm(?string $formType = null, array $options = []): void
    {
        $formType = !empty($formType) ? $formType : DefaultType::class;
        if (DefaultType::class === $formType) {
            $options['data_class'] = $this->coreLocator->em()->getMetadataFactory()->getMetadataFor(get_class($this->entity))->getName();
        }
        if (empty($options['website'])) {
            $options['website'] = $this->website;
        }

        if (isset($options['form_name'])) {
            $formName = $options['form_name'];
            unset($options['form_name']);
            $options['data_class'] = get_class($this->entity);
            $this->form = $this->formFactory->createNamedBuilder($formName, $formType, $this->entity, $options)->getForm();
        } else {
            try {
                $this->form = $this->formFactory->create($formType, $this->entity, $options);
            } catch (\Exception $exception) {
                throw new HttpException($exception->getCode(), $exception->getMessage());
            }
        }
    }

    /**
     * Form submission process.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface|InvalidArgumentException
     */
    public function submit(mixed $formManager = null): void
    {
        try {
            if ($this->form) {
                $this->globalManager->setForm($this->form);
                $this->form->handleRequest($this->request);
                if ($this->form->isSubmitted() && $this->form->isValid()) {
                    $this->globalManager->success($this->interface, $formManager, $this->disableFlash);
                    $this->setRedirection(null, $this->form->getData());
                } elseif ($this->form->isSubmitted() && !$this->form->isValid()) {
                    $this->globalManager->invalid($this->form);
                }
            }
        } catch (\Exception $exception) {
            $session = new Session();
            $message = $exception->getMessage();
            $this->logger($exception);
            if (!$message && !is_string($message) && method_exists($exception, 'getPrevious')) {
                $previous = $exception->getPrevious();
                if (method_exists($previous, 'getTrace') && is_iterable($previous->getTrace())) {
                    foreach ($previous->getTrace() as $trace) {
                        if (is_array($trace) && !empty($trace['file']) && !empty($trace['args'][0]) && !empty($trace['args'][2]) && is_array($trace['args'])) {
                            $message = $trace['args'][0];
                            if (is_array($trace['args'][2])) {
                                foreach ($trace['args'][2] as $pattern => $value) {
                                    $message = str_replace($pattern, $value, $message);
                                }
                                break;
                            }
                        }
                    }
                }
            }
            $session->getFlashBag()->add('error', $message);
        }
    }

    /**
     * Get Redirection.
     */
    public function getRedirection(): ?string
    {
        return $this->redirection;
    }

    /**
     * Set Redirection.
     *
     * @throws InvalidArgumentException
     */
    public function setRedirection(?string $redirection = null, mixed $entity = null): void
    {
        try {
            $session = new Session();
            $clickedButton = $this->form->getClickedButton();
            $clickedButtonName = is_object($clickedButton) && method_exists($clickedButton, 'getName') ? $clickedButton->getName() : null;
            $saveEditRoute = 'admin_'.$this->interface['name'].'_edit';
            $interfaceName = ($this->interface['entity'] instanceof Block || $this->interface['entity'] instanceof Zone)
                ? $this->request->get('interfaceName') : $this->interface['name'];
            $saveLayoutRoute = 'admin_'.$interfaceName.'_layout';
            $saveAddRoute = 'admin_'.$interfaceName.'_index';
            $currentRoute = $this->request->get('_route');
            $parameters = $this->getRouteParameters($saveEditRoute, $entity);
            if (!empty($redirection)) {
                $this->redirection = $redirection;
            } elseif ($this->appExtension->routeExist($saveLayoutRoute) && 'saveBack' === $clickedButtonName
                && ($this->interface['entity'] instanceof Block || $this->interface['entity'] instanceof Zone)) {
                $this->redirection = $this->coreLocator->router()->generate($saveLayoutRoute, $parameters);
            } elseif ('saveAdd' === $clickedButtonName && $this->appExtension->routeExist($saveAddRoute)) {
                $parameters = $this->coreLocator->routeArgs($saveAddRoute, $entity);
                $parameters['open_modal'] = true;
                $this->redirection = $this->coreLocator->router()->generate($saveAddRoute, $parameters);
            } elseif ('saveEdit' === $clickedButtonName && $this->appExtension->routeExist($saveEditRoute)) {
                $this->redirection = $this->coreLocator->router()->generate($saveEditRoute, $parameters);
            } elseif ('saveEdit' === $clickedButtonName && $this->appExtension->routeExist($saveLayoutRoute)) {
                $this->redirection = $this->coreLocator->router()->generate($saveLayoutRoute, $parameters);
            } elseif ('saveBack' === $clickedButtonName) {
                $lastRoute = $session->get('last_route_back');
                if (is_object($lastRoute) && property_exists($lastRoute, 'name')) {
                    $this->redirection = $this->coreLocator->router()->generate($lastRoute->name, $lastRoute->params);
                } elseif (!$this->redirection && str_contains($currentRoute, '_edit') && $this->appExtension->routeExist($saveAddRoute)) {
                    if(isset($parameters[$interfaceName])) {
                        unset($parameters[$interfaceName]);
                    }
                    $this->redirection = $this->coreLocator->router()->generate($saveAddRoute, $parameters);
                }
                if (!$this->redirection) {
                    $this->redirection = $this->request->headers->get('referer');
                }
                if ($session->get('last_route_back_page')) {
                    $this->redirection = $this->redirection.'?page='.$session->get('last_route_back_page');
                }
            } else {
                $this->redirection = $this->request->headers->get('referer');
            }
        } catch (\Exception|InvalidArgumentException $exception) {
            $this->logger($exception);
            $this->redirection = $this->request->headers->get('referer');
        }
    }

    /**
     * Get route para.
     *
     * @throws NonUniqueResultException
     */
    private function getRouteParameters(string $saveEditRoute, mixed $entity = null): array
    {
        $parameters = [];
        $parameters['website'] = $this->website->getId();
        $routeInfos = $this->coreLocator->router()->getRouteCollection()->get($saveEditRoute);
        $interfaceNameParameter = $this->request->get('interfaceName');
        $interfaceEntityParameter = $this->request->get('interfaceEntity');

        if ($routeInfos) {
            if (str_contains($routeInfos->getPath(), '{entitylocale}')) {
                $parameters['entitylocale'] = $this->request->get('entitylocale')
                    ? $this->request->get('entitylocale')
                    : $this->website->getConfiguration()->getLocale();
            }
        }

        if ($this->interface['entity'] instanceof Block || $this->interface['entity'] instanceof Zone) {
            $parameters[$interfaceNameParameter] = intval($interfaceEntityParameter);
        } else {
            $parameters[$this->interface['name']] = $this->form->getData()->getId();
            if (!empty($this->interface['masterFieldId']) && 'configuration' !== $this->interface['masterField']) {
                $parameters[$this->interface['masterField']] = $this->interface['masterFieldId'];
            }
        }

        if ($routeInfos) {
            preg_match_all('/\{([^}]*)\}/', $routeInfos->getPath(), $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    if (empty($parameters[$match])) {
                        if ($this->request->get($match)) {
                            $parameters[$match] = $this->request->get($match);
                        } elseif (is_object($entity) && method_exists($entity, 'getId')) {
                            $interface = $this->coreLocator->interfaceHelper()->generate(get_class($entity));
                            if (!empty($interface['name']) && $match === $interface['name']) {
                                $parameters[$match] = $entity->getId();
                            }
                        }
                    }
                }
            }
        }

        return $parameters;
    }

    /**
     * Logger.
     */
    private function logger(\Exception $exception): void
    {
        $logger = new Logger('form.helper');
        $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/admin.log', 10, Level::Critical));
        $logger->critical($exception->getMessage().' at '.get_class($this).' line '.$exception->getLine());
        if (!str_contains($exception->getMessage(), 'saveEdit') && !str_contains($exception->getMessage(), 'save')) {
            $session = new Session();
            $session->getFlashBag()->add('error', $exception->getMessage());
        }
    }
}
