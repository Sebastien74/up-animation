<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Form\Manager as FormManager;
use App\Service\Admin as AdminService;
use App\Twig\Core\AppRuntime;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * AdminFormMangerLocator.
 *
 * To load admin Services
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AdminLocator implements AdminLocatorInterface
{
    /**
     * AdminFormMangerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(AdminService\FormHelper::class, indexAttribute: 'key')] protected ServiceLocator $formHelperLocator,
        #[AutowireLocator(AdminService\TreeHelper::class, indexAttribute: 'key')] protected ServiceLocator $treeHelperLocator,
        #[AutowireLocator(AdminService\IndexHelper::class, indexAttribute: 'key')] protected ServiceLocator $indexHelperLocator,
        #[AutowireLocator(AdminService\FormDuplicateHelper::class, indexAttribute: 'key')] protected ServiceLocator $formDuplicateLocator,
        #[AutowireLocator(AdminService\ClearMediasService::class, indexAttribute: 'key')] protected ServiceLocator $clearMediasServiceLocator,
        #[AutowireLocator(AdminService\SearchFilterService::class, indexAttribute: 'key')] protected ServiceLocator $searchFilterServiceLocator,
        #[AutowireLocator(AdminService\VideoService::class, indexAttribute: 'key')] protected ServiceLocator $videoServiceLocator,
        #[AutowireLocator(AdminService\PositionService::class, indexAttribute: 'key')] protected ServiceLocator $positionServiceLocator,
        #[AutowireLocator(AdminService\TitleService::class, indexAttribute: 'key')] protected ServiceLocator $titleServiceLocator,
        #[AutowireLocator(AdminService\DeleteService::class, indexAttribute: 'key')] protected ServiceLocator $deleteServiceLocator,
        #[AutowireLocator(FormManager\Core\GlobalManager::class, indexAttribute: 'key')] protected ServiceLocator $globalLocator,
        #[AutowireLocator(FormManager\Seo\UrlManager::class, indexAttribute: 'key')] protected ServiceLocator $urlManagerLocator,
        #[AutowireLocator(FormManager\Layout\LayoutManager::class, indexAttribute: 'key')] protected ServiceLocator $layoutManagerLocator,
        #[AutowireLocator(FormManager\Core\TreeManager::class, indexAttribute: 'key')] protected ServiceLocator $treeManagerLocator,
        #[AutowireLocator(FormManager\Translation\IntlManager::class, indexAttribute: 'key')] protected ServiceLocator $intlManagerLocator,
        private readonly DeleteInterface $deleteInterface,
        private readonly ExportInterface $exportInterface,
        private readonly ImportInterface $importInterface,
        private readonly CoreLocatorInterface $coreLocator,
        private readonly AppRuntime $appRuntime,
    ) {
    }

    /**
     * To get FormHelper.
     *
     * @throws ContainerExceptionInterface
     */
    public function formHelper(): AdminService\FormHelper
    {
        return $this->formHelperLocator->get('form_helper');
    }

    /**
     * To get TreeHelper.
     *
     * @throws ContainerExceptionInterface
     */
    public function treeHelper(): AdminService\TreeHelper
    {
        return $this->treeHelperLocator->get('tree_helper');
    }

    /**
     * To get IndexHelper.
     *
     * @throws ContainerExceptionInterface
     */
    public function indexHelper(): AdminService\IndexHelper
    {
        return $this->indexHelperLocator->get('index_helper');
    }

    /**
     * To get FormDuplicateHelper.
     *
     * @throws ContainerExceptionInterface
     */
    public function formDuplicateHelper(): AdminService\FormDuplicateHelper
    {
        return $this->formDuplicateLocator->get('form_duplicate_helper');
    }

    /**
     * To get ClearMediasService.
     *
     * @throws ContainerExceptionInterface
     */
    public function clearMediasService(): AdminService\ClearMediasService
    {
        return $this->clearMediasServiceLocator->get('clear_medias_service');
    }

    /**
     * To get SearchFilterService.
     *
     * @throws ContainerExceptionInterface
     */
    public function searchFilterService(): AdminService\SearchFilterService
    {
        return $this->searchFilterServiceLocator->get('search_filter_service');
    }

    /**
     * To get VideoService.
     *
     * @throws ContainerExceptionInterface
     */
    public function videoService(): AdminService\VideoService
    {
        return $this->videoServiceLocator->get('video_service');
    }

    /**
     * To get PositionService.
     *
     * @throws ContainerExceptionInterface
     */
    public function positionService(): AdminService\PositionService
    {
        return $this->positionServiceLocator->get('position_service');
    }

    /**
     * To get DeleteService.
     *
     * @throws ContainerExceptionInterface
     */
    public function deleteService(): AdminService\DeleteService
    {
        return $this->deleteServiceLocator->get('core_delete_service');
    }

    /**
     * To get TitleService.
     *
     * @throws ContainerExceptionInterface
     */
    public function titleService(): AdminService\TitleService
    {
        return $this->titleServiceLocator->get('title_service');
    }

    /**
     * To get UrlManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function globalManager(): FormManager\Core\GlobalManager
    {
        return $this->globalLocator->get('core_global_form_manager');
    }

    /**
     * To get UrlManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function urlManager(): FormManager\Seo\UrlManager
    {
        return $this->urlManagerLocator->get('seo_url_form_manager');
    }

    /**
     * To get LayoutManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function layoutManager(): FormManager\Layout\LayoutManager
    {
        return $this->layoutManagerLocator->get('layout_form_manager');
    }

    /**
     * To get TreeManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function treeManager(): FormManager\Core\TreeManager
    {
        return $this->treeManagerLocator->get('core_tree_form_manager');
    }

    /**
     * To get TreeManager.
     *
     * @throws ContainerExceptionInterface
     */
    public function intlManager(): FormManager\Translation\IntlManager
    {
        return $this->intlManagerLocator->get('intl_form_manager');
    }

    /**
     * To get DeleteInterface.
     */
    public function deleteManagers(): DeleteInterface
    {
        return $this->deleteInterface;
    }

    /**
     * To get ImportInterface.
     */
    public function importManagers(): ImportInterface
    {
        return $this->importInterface;
    }

    /**
     * To get ExportInterface.
     */
    public function exportManagers(): ExportInterface
    {
        return $this->exportInterface;
    }

    /**
     * To get too large files.
     *
     * @throws ContainerExceptionInterface|NonUniqueResultException
     */
    public function tooHeavyFiles(mixed $entity): array
    {
        $filesystem = new Filesystem();
        $tooHeavyFiles = [];
        if ($entity && method_exists($entity, 'getMediaRelations')) {
            $classname = str_replace('Proxies\__CG__\\', '', get_class($entity));
            $interface = $this->coreLocator->interfaceHelper()->generate($classname);
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                $media = $mediaRelation->getMedia();
                if ($media) {
                    $website = $media->getWebsite();
                    $dirname = $this->coreLocator->projectDir().'/public/uploads/'.$website->getUploadDirname().'/'.$media->getFilename();
                    $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
                    if ($filesystem->exists($dirname)) {
                        $info = $this->coreLocator->fileInfo()->file($website, $media->getFilename());
                        if ($info->getSize() > 500000) {
                            $locales = !empty($tooHeavyFiles[$info->getFilename()][$entity->getId()]['locales']) ? $tooHeavyFiles[$info->getFilename()][$entity->getId()]['locales'] : [];
                            $locales[] = $mediaRelation->getLocale();
                            $tooHeavyFiles[$info->getFilename()][$entity->getId()] = [
                                'filename' => $info->getFilename(),
                                'locales' => $locales,
                                'entity' => $entity,
                                'interface' => $interface,
                                'interfaceName' => !empty($interface['name']) ? $interface['name'] : null,
                                'bytes' => $info->getFormatBytes(),
                            ];
                        }
                    }
                }
            }
        }

        return $tooHeavyFiles;
    }

    /**
     * To check attr alt is missing.
     */
    public function mediasAlert(mixed $entity): array
    {
        $response = [];

        if (method_exists($entity, 'getMediaRelations') && $entity->getId()) {
            $metadata = $this->coreLocator->metadata($entity, 'mediaRelations');
            $mediaRelations = $this->coreLocator->em()->getRepository($metadata->targetEntity)->createQueryBuilder('mr')
                ->leftJoin('mr.intl', 'i')
                ->leftJoin('mr.media', 'm')
                ->andWhere('mr.'.$metadata->mappedBy.' = :mappedBy')
                ->andWhere('i.title IS NULL')
                ->andWhere('m.filename IS NOT NULL')
                ->setParameter('mappedBy', $entity)
                ->getQuery()
                ->getResult();
            foreach ($mediaRelations as $mediaRelation) {
                $entityId = $entity->getId();
                $filename = $mediaRelation->getMedia()->getFilename();
                $locales = !empty($response[$filename][$entityId]['locales']) ? $response[$filename][$entityId]['locales'] : [];
                $locales[] = $mediaRelation->getLocale();
                $response[$filename][$entityId] = [
                    'locales' => $locales,
                ];
            }
        }

        return $response;
    }

    /**
     * To get route arguments.
     */
    public function routeArgs(string $route, mixed $entity = null, array $parameters = []): array
    {
        return $this->appRuntime->routeArgs($route, $entity, $parameters);
    }
}
