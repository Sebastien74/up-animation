<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Form\Interface as FormManagerInterface;
use App\Service\Core\Urlizer;
use App\Service\Interface as ServiceLocator;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * GlobalManager.
 *
 * Manage all admin forms
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => GlobalManager::class, 'key' => 'core_global_form_manager'],
])]
class GlobalManager
{
    private ?Request $request;
    private ?Request $masterRequest;
    private array $interface = [];
    private ?object $manager = null;
    private object $data;
    private ?string $masterField;
    private FormInterface $form;
    private Website $website;

    /**
     * GlobalManager constructor.
     */
    public function __construct(
        private readonly FormManagerInterface\CoreFormManagerInterface $coreManagerLocator,
        private readonly FormManagerInterface\LayoutFormManagerInterface $layoutManager,
        private readonly FormManagerInterface\MediaFormManagerInterface $mediaManager,
        private readonly FormManagerInterface\IntlFormManagerInterface $intlManager,
        private readonly ServiceLocator\CoreLocatorInterface $coreLocator,
        private readonly ServiceLocator\AdminLocatorInterface $adminLocator,
    ) {
        $this->request = $this->coreLocator->requestStack()->getCurrentRequest();
        $this->masterRequest = $this->coreLocator->requestStack()->getMainRequest();
    }

    /**
     * On success submission.
     *
     * @throws ContainerExceptionInterface|NotFoundExceptionInterface
     */
    public function success(array $interface, mixed $formManager = null, bool $disableFlash = false): void
    {
        try {
            $this->interface = $interface;
            $this->manager = $formManager;

            $this->data = $this->form->getData();

            $this->setWebsite($interface);
            $isNew = !$this->data->getId();

            if ($isNew) {
                $this->setMasterField($interface);
                $this->setParentMasterField($interface);
                $this->setPosition($interface);
                $this->callManager('prePersist', $interface);
            } else {
                $this->setIsDefault($interface);
                $this->callManager('preUpdate', $interface);
            }

            $this->setAdminName();
            $this->setSlug();
            $this->setComputeETag();

            $this->request->getSession()->set('entityPostClassname', get_class($this->data));
            $this->adminLocator->urlManager()->post($this->form, $this->website);
            $this->layoutManager->layout()->post($interface, $this->form, $this->website);
            $this->mediaManager->media()->post($this->form, $this->website, $interface);
            $this->intlManager->intl()->post($this->form, $this->website, $isNew);

            if ($this->data instanceof Layout\Block) {
                $this->setZone();
            }

            $this->coreLocator->em()->persist($this->data);
            $this->coreLocator->em()->flush();

            $this->callManager('onFlush', $interface);
            $this->dispatchEvent();

            $this->coreManagerLocator->session()->execute($this->request, $this->data);

            $this->setFlashBag($isNew, 'success', $disableFlash, $interface);

            $clearMediasEntities = [Layout\Page::class, Layout\Block::class];
            if (!empty($this->interface['classname']) && in_array($this->interface['classname'], $clearMediasEntities)) {
                $clearMediasService = $this->adminLocator->clearMediasService();
                $clearMediasService->execute($this->interface['classname']);
            }
        } catch (\Exception $exception) {
            $session = new Session();
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans('Une erreur est survenue : ', [], 'admin').$exception->getMessage());
            $logger = new Logger('form.global.manager');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/admin-critical.log', 10, Level::Info));
            $logger->info('[STACKTRACE] '.$exception->getTraceAsString());
            $logger->critical($exception->getMessage().' at '.get_class($this).' - File '.$exception->getFile().' line '.$exception->getLine());
        }
    }

    /**
     * On invalid submission.
     */
    public function invalid(FormInterface $form): void
    {
        $session = new Session();
        $message = $this->getErrors($form, $session, '');
        if ($message) {
            $session->getFlashBag()->add('error', rtrim($message, '<br>'));
        }
    }

    /**
     * Get error message.
     */
    private function getErrors(FormInterface $form, Session $session, string $message): string
    {
        $disabledRoutes = ['admin_redirection_edit'];
        $route = $this->masterRequest->get('_route');
        if ($route && in_array($route, $disabledRoutes)) {
            return '';
        }

        if ($this->coreLocator->isDebug()) {
            if ($form->getErrors()->count() > 0) {
                $message .= 'Une erreur est survenue ! <span class="badge badge-danger d-inline-block mx-1">DEV MODE MESSAGE</span>';
            }
            foreach ($form->getErrors() as $error) {
                $message .= $error->getMessage();
                if (method_exists($error, 'getCause') && $error->getCause()
                    && is_object($error->getCause()) && method_exists($error->getCause(), 'getPropertyPath')) {
                    $message .= ' ['.$error->getCause()->getPropertyPath().']<br>';
                }
                $message .= '<br>';
            }
        }

        foreach ($form->all() as $childForm) {
            if ($childForm->getErrors()->count()) {
                return $this->getErrors($childForm, $session, $message);
            }
            foreach ($childForm->all() as $subChildForm) {
                if ($subChildForm->getErrors()->count()) {
                    return $this->getErrors($subChildForm, $session, $message);
                }
                foreach ($subChildForm->all() as $subSubChildForm) {
                    if ($subSubChildForm->getErrors()->count()) {
                        return $this->getErrors($subSubChildForm, $session, $message);
                    }
                }
            }
        }

        $sameFileError = $session->get('same_file_error');
        if ($sameFileError) {
            $message .= $sameFileError;
            $session->remove('same_file_error');
        }

        return $message ?: (!$form->isValid() ? $this->coreLocator->translator()->trans("Le formulaire n'est pas valide ", [], 'admin')
            : $this->coreLocator->translator()->trans('Une erreur est survenue !', [], 'admin'));
    }

    /**
     * Set WebsiteModel.
     */
    public function setForm(FormInterface $form): void
    {
        $this->form = $form;
    }

    /**
     * Set WebsiteModel.
     */
    public function setWebsite(array $interface): void
    {
        $this->website = $interface['website'] instanceof Website ?
            $interface['website'] : ($this->masterRequest->get('website')
                ? $this->coreLocator->em()->getRepository(Website::class)->find($this->masterRequest->get('website')) : null);
        if (method_exists($this->data, 'getWebsite') && !$this->data->getWebsite()) {
            $this->data->setWebsite($this->website);
        }
    }

    /**
     * Set masterField.
     */
    public function setMasterField(array $interface): void
    {
        $this->masterField = $interface['masterField'];
        if ('website' === $this->masterField) {
            $this->data->setWebsite($this->website);
        } elseif ('configuration' === $this->masterField) {
            $this->data->setConfiguration($this->website->getConfiguration());
        }
    }

    /**
     * Set parentMasterField.
     */
    public function setParentMasterField(array $interface): void
    {
        $parentMasterField = $interface['parentMasterField'];
        $parentRequest = $parentMasterField ? $this->masterRequest->get($parentMasterField) : null;
        $mapping = $this->coreLocator->em()->getClassMetadata($interface['classname'])->getAssociationMappings();
        $setter = $interface['parentMasterField'] ? 'set'.ucfirst($interface['parentMasterField']) : null;
        if ($setter && !empty($parentRequest) && method_exists($this->data, $setter) && !empty($mapping[$parentMasterField])) {
            $parent = $this->coreLocator->em()->getRepository($mapping[$parentMasterField]['targetEntity'])->find(intval($parentRequest));
            $this->data->$setter($parent);
        }
    }

    /**
     * Set position.
     */
    public function setPosition(array $interface): void
    {
        $indexHelper = $this->adminLocator->indexHelper();
        $indexHelper->setDisplaySearchForm(false);
        $indexHelper->execute($interface['classname'], $interface);
        $pagination = $indexHelper->getPagination();
        $position = method_exists($pagination, 'getTotalItemCount') ? $pagination->getTotalItemCount() + 1 : 1;
        if (method_exists($this->data, 'setPosition')) {
            $this->data->setPosition($position);
        }
    }

    /**
     * Set is default unique.
     */
    public function setIsDefault(array $interface): void
    {
        if (method_exists($this->data, 'setAsDefault') && !empty($interface['classname']) && Configuration::class !== $interface['classname']) {
            $existing = $this->coreLocator->em()->getRepository($interface['classname'])->findOneBy([
                'website' => $this->website,
                'asDefault' => true,
            ]);
            if ($existing && $this->data->isAsDefault() && $existing->getId() !== $this->data->getId()) {
                $existing->setAsDefault(false);
                $this->coreLocator->em()->persist($existing);
            }
        }
    }

    /**
     * Set Zone.
     */
    public function setZone(): void
    {
        $zone = $this->data->getCol()->getZone();
        $haveTitle = false;
        foreach ($zone->getCols() as $col) {
            foreach ($col->getBlocks() as $block) {
                foreach ($block->getIntls() as $intl) {
                    if ($intl->getTitle()) {
                        $haveTitle = true;
                    }
                }
            }
        }
        if (!$haveTitle) {
            $zone->setAsSection(false);
            $this->coreLocator->em()->persist($zone);
        }
    }

    /**
     * Set adminName.
     */
    public function setAdminName(): void
    {
        if (method_exists($this->data, 'getAdminName') && empty($this->data->getAdminName())) {
            if (method_exists($this->data, 'getIntl') && !empty($this->data->getIntl())) {
                $intl = $this->data->getIntl();
                $this->data->setAdminName($intl->getTitle());
            }
            if (method_exists($this->data, 'getIntls')) {
                foreach ($this->data->getIntls() as $intl) {
                    if ($intl->getLocale() === $this->website->getConfiguration()->getLocale()) {
                        $this->data->setAdminName($intl->getTitle());
                    }
                }
            }
        }
    }

    /**
     * Set slug.
     */
    private function setSlug(): void
    {
        if (method_exists($this->data, 'getSlug')
            && method_exists($this->data, 'getAdminName')
            && empty($this->data->getSlug()) && !empty($this->interface['classname'])) {
            $queryBuilder = $this->coreLocator->em()->getRepository($this->interface['classname'])->createQueryBuilder('e');
            if (!empty($this->masterField) && !empty($this->masterRequest->get($this->masterField))) {
                $queryBuilder->andWhere('e.'.$this->masterField.' = :'.$this->masterField);
                $queryBuilder->setParameter($this->masterField, $this->masterRequest->get($this->masterField));
            }

            $slug = Urlizer::urlize($this->data->getAdminName());
            $queryBuilder->andWhere('e.slug = :slug');
            $queryBuilder->setParameter('slug', $slug);
            $existing = $queryBuilder->getQuery()->getResult();
            $slug = $existing && $slug ? $slug.'-'.uniqid() : $slug;

            $this->data->setSlug($slug);
            $this->coreLocator->em()->persist($this->data);
        }
    }

    /**
     * Set Compute ETag.
     */
    private function setComputeETag(): void
    {
        if (method_exists($this->data, 'setComputeETag') && empty($this->data->getComputeETag())) {
            $this->data->setComputeETag(uniqid().md5(strval($this->data->getId())));
            $this->coreLocator->em()->persist($this->data);
        }
    }

    /**
     * Set Session Flash bag.
     */
    private function setFlashBag(bool $isNew, string $type, bool $disableFlash, array $interface = []): void
    {
        $isDisabled = $disableFlash || !empty($interface['disabled_flash_bag']) && $interface['disabled_flash_bag'];
        if (!$isDisabled) {
            $message = $isNew ? $this->coreLocator->translator()->trans('Créé avec succès !!', [], 'admin')
                : $this->coreLocator->translator()->trans('Modifié avec succès !!', [], 'admin');
            $session = new Session();
            $session->getFlashBag()->add($type, $message);
        }
    }

    /**
     * Call form manager.
     */
    private function callManager(string $method, array $interface): void
    {
        if (is_object($this->manager) && method_exists($this->manager, $method)) {
            $this->manager->$method($this->data, $this->website, $interface, $this->form);
        }
    }

    /**
     * Dispatch event.
     */
    private function dispatchEvent(array $interface = [], mixed $data = null): void
    {
        $interface = $interface ?: $this->interface;
        $data = $data ?: $this->data;
        $classname = !empty($interface['classname']) ? $interface['classname'] : null;
        $postType = is_object($data) && method_exists($data, 'getId') && $data->getId()
            ? 'Updated' : 'Created';
        $matches = explode('\\', $classname);
        $eventName = '\App\Event\\'.end($matches).$postType.'Event';
        $subscriberName = '\App\EventSubscriber\\'.end($matches).'Subscriber';

        if ($classname && class_exists($eventName) && class_exists($subscriberName)) {
            $dispatcher = new EventDispatcher();
            $subscriber = new $subscriberName();
            $dispatcher->addSubscriber($subscriber);
            $event = new $eventName($data);
            $dispatcher->dispatch($event, $eventName::NAME);
        }
    }
}
