<?php

declare(strict_types=1);

namespace App\Form\Manager\Media;

use App\Entity\BaseMediaRelation;
use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Media;
use App\Entity\Module\Map\Point;
use App\Entity\Seo\Seo;
use App\Service\Core\InterfaceHelper;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MediaManager.
 *
 * Manage admin Media form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => MediaManager::class, 'key' => 'media_form_manager'],
])]
class MediaManager
{
    private const array SCREENS = ['tablet', 'mobile'];
    private const array ALLOWED_IMAGES_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp'];

    private TranslatorInterface $translator;
    private ?Request $request;
    private EntityManagerInterface $entityManager;
    private ?string $interfaceName = null;
    private array $localesSet = [];

    /**
     * MediaManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Uploader $uploader,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->request = $this->coreLocator->request();
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    /**
     * Synchronize locales MediaRelations if no exist.
     *
     * @throws NonUniqueResultException
     */
    public function synchronizeLocales(Website $website, array $interface, mixed $entity, mixed $mediaRelation): void
    {
        $this->localesSet = [];
        foreach ($website->getConfiguration()->getAllLocales() as $locale) {
            $this->setEntity($interface, $locale, $entity, $mediaRelation);
        }
    }

    /**
     * Synchronize locale Media screens.
     *
     * @throws ORMException
     */
    public function synchronizeScreens(Media\Media $media): void
    {
        if (in_array($media->getExtension(), self::ALLOWED_IMAGES_EXTENSIONS)) {
            foreach (self::SCREENS as $screen) {
                $exist = $this->screenExist($media, $screen);
                if (!$exist) {
                    $mediaScreen = new Media\Media();
                    $mediaScreen->setMedia($media);
                    $mediaScreen->setScreen($screen);
                    $mediaScreen->setWebsite($media->getWebsite());
                    $media->addMediaScreen($mediaScreen);
                    $this->entityManager->persist($media);
                    if (!$this->request->isMethod('post')) {
                        $this->entityManager->flush();
                        $this->entityManager->refresh($media);
                    }
                }
            }
        }
    }

    /**
     * Get default locale Media
     * Get default locale Media.
     */
    private function screenExist(Media\Media $media, string $screen): bool
    {
        foreach ($media->getMediaScreens() as $mediaScreen) {
            if ($mediaScreen->getScreen() === $screen) {
                return true;
            }
        }

        return false;
    }

    /**
     * Post Media.
     *
     * @throws NonUniqueResultException|Exception
     */
    public function post(FormInterface $form, Website $website, array $interface = []): void
    {
        $entity = $form->getData();
        $this->interfaceName = !empty($interface['name']) ? $interface['name'] : null;

        $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        $metadata = $metadata->getAssociationMappings();
        $asMediaRelation = !empty($metadata['media']['targetEntity']) && Media\Media::class === $metadata['media']['targetEntity'];

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            $this->entityManager->persist($entity);
        }

        /* Media screen */
        if ($entity instanceof Media\Media) {
            foreach ($form['mediaScreens'] as $mediaScreenForm) {
                $uploadedFile = $mediaScreenForm['uploadedFile']->getData();
                $media = $mediaScreenForm->getData();
                if ($uploadedFile) {
                    $this->setUploadedMedia($uploadedFile, $media, $website);
                }
            }
        }

        $this->setGeoJson($entity, $form, $website);

        /* Media video */
        if ($entity instanceof Layout\Block && 'video' === $form->getName()) {
            foreach ($form['mediaRelations'] as $mediaRelation) {
                foreach ($mediaRelation['media']['mediaScreens'] as $screen) {
                    $uploadedFile = $screen['uploadedFile']->getData();
                    if ($uploadedFile) {
                        $this->setUploadedMedia($uploadedFile, $screen->getData(), $website);
                    }
                }
            }
        }

        /* Entity Video collection */
        foreach ($form->all() as $childForm) {
            $childData = $childForm->getData();
            if ($childData instanceof PersistentCollection) {
                foreach ($childForm->all() as $childChildForm) {
                    $childChildData = $childChildForm->getData();
                    if (is_object($childChildData) && method_exists($childChildData, 'getMediaRelations') && 'videos' === $childForm->getName()) {
                        foreach ($childChildForm['mediaRelations'] as $mediaRelation) {
                            $uploadedFile = $mediaRelation['media']['uploadedFile']->getData();
                            if ($uploadedFile) {
                                $this->setUploadedMedia($uploadedFile, $mediaRelation->getData()->getMedia(), $website);
                            }
                            foreach ($mediaRelation['media']['mediaScreens'] as $screen) {
                                $uploadedFile = $screen['uploadedFile']->getData();
                                if ($uploadedFile) {
                                    $this->setUploadedMedia($uploadedFile, $screen->getData(), $website);
                                }
                            }
                        }
                    }
                }
            } elseif ($childData instanceof Media\Media && 'poster' === $childData->getScreen() && !empty($form['media'])) {
                foreach ($form['media']['mediaScreens'] as $screen) {
                    $uploadedFile = $screen['uploadedFile']->getData();
                    if ($uploadedFile) {
                        $this->setUploadedMedia($uploadedFile, $screen->getData(), $website);
                    }
                }
            }
        }

        /* Multiple uploaded files */
        if (!empty($form['medias'])) {
            foreach ($form['medias'] as $uploadedFilesForm) {
                $uploadedFiles = $uploadedFilesForm->getData();
                foreach ($uploadedFiles as $uploadedFile) {
                    $this->multiUploadedFiles($uploadedFile, $website, $entity);
                }
            }
        } /* Update Media (in media library) */
        elseif (!empty($form['uploadedFile'])) {
            $uploadedFile = $form['uploadedFile']->getData();
            if ($uploadedFile) {
                $this->setUploadedMedia($uploadedFile, $entity, $website);
            }
        } /* Single uploaded file mediaRelations Collection */
        elseif (method_exists($entity, 'getMediaRelations') && !empty($form['mediaRelations'])) {
            $this->singleUploadedFile($form, $website, $entity);
        } /* Single uploaded file mediaRelation */
        elseif (method_exists($entity, 'getMediaRelation') && !empty($form['mediaRelation'])) {
            $this->singleUploadedLocaleFile($form, $website, $entity);
        } /* Single uploaded file MediaRelation entity */
        elseif ($asMediaRelation) {
            $uploadedFile = $this->request->files->get('media_relation') ? $this->request->files->get('media_relation')['media']['uploadedFile'] : null;
            if (!$uploadedFile && !empty($this->request->files->get('media_relation_'.$entity->getId())['media']['uploadedFile'])) {
                $uploadedFile = $this->request->files->get('media_relation_'.$entity->getId())['media']['uploadedFile'];
            }
            if ($uploadedFile) {
                $this->setUploadedMediaMediaRelation($uploadedFile, $entity, $entity->getMedia(), $website);
            }
        }

        if ($entity instanceof Media\Media) {
            $this->setMedia($entity);
            if (!$entity->getWebsite()) {
                $entity->setWebsite($website);
            }
        }

        if ($asMediaRelation) {
            $intl = method_exists($entity, 'getIntl') ? $entity->getIntl() : null;
            if ($intl && method_exists($intl, 'getWebsite') && !$intl->getWebsite()) {
                $intl->setWebsite($website);
            }
            $media = $entity->getMedia();
            if ($media && !$media->getWebsite()) {
                $media->setWebsite($website);
            }
        }

        if (method_exists($entity, 'getMediaRelations')) {
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                if (method_exists($mediaRelation, 'getIntl') && $mediaRelation->getIntl() && !$mediaRelation->getIntl()->getWebsite()) {
                    $intl = $mediaRelation->getIntl();
                    $intl->setWebsite($website);
                }
            }
        }

        $media = $asMediaRelation && !$entity instanceof Media\Media ? $entity->getMedia() : $entity;
        if ($media instanceof Media\Media) {
            foreach ($media->getMediaScreens() as $mediaScreen) {
                if ($mediaScreen->getFilename()) {
                    $media->setHaveMediaScreens(true);
                    $this->entityManager->persist($media);
                    break;
                }
            }
        }
    }

    /**
     * Multi uploaded files.
     *
     * @throws NonUniqueResultException
     */
    private function multiUploadedFiles(UploadedFile $uploadedFile, Website $website, mixed $entity): void
    {
        $configuration = $website->getConfiguration();

        $isUpload = $this->uploader->upload($uploadedFile, $website);

        if ($isUpload) {

            $media = new Media\Media();
            $media->setFilename($this->uploader->getFilename());
            $media->setName($this->uploader->getName());
            $media->setExtension($this->uploader->getExtension());
            $media->setWebsite($website);

            $this->entityManager->persist($media);

            if (!$entity instanceof Website && property_exists($entity, 'mediaRelations')) {

                $classname = $this->entityManager->getClassMetadata(get_class($entity))->getName();
                $repository = $this->entityManager->getRepository($classname);
                $queryForPosition = $repository->createQueryBuilder('e')->select('e')
                    ->leftJoin('e.mediaRelations', 'm')
                    ->andWhere('m.locale = :locale')
                    ->andWhere('e.id = :id')
                    ->setParameter('locale', $configuration->getLocale())
                    ->setParameter('id', $entity->getId())
                    ->addSelect('m')
                    ->getQuery()
                    ->getOneOrNullResult();
                $position = $queryForPosition ? $queryForPosition->getMediaRelations()->count() + 1 : 1;

                $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
                $mediaRelation = new ($mediaRelationData->targetEntity)();
                $mediaRelation->setLocale($configuration->getLocale());
                $mediaRelation->setMedia($media);
                $mediaRelation->setPosition($position);
                $mediaRelation->setCategorySlug($this->interfaceName);

                $entity->addMediaRelation($mediaRelation);
                $this->setIntlMedia($mediaRelation, $media);
                $this->entityManager->persist($mediaRelation);
                $this->initializeLocales($configuration, $entity, $website);
            }
        }
    }

    /**
     * Single uploaded file.
     */
    private function singleUploadedFile(FormInterface $form, Website $website, mixed $entity): void
    {
        $configuration = $website->getConfiguration();
        foreach ($form->get('mediaRelations') as $relation) {
            $uploadedFile = $relation['media']['uploadedFile']->getData();
            $mediaRelation = $relation->getData();
            $media = $relation['media']->getData();
            if (!$media->getWebsite()) {
                $media->setWebsite($website);
            }
            if ($uploadedFile) {
                $this->setUploadedMediaMediaRelation($uploadedFile, $mediaRelation, $media, $website);
            }
        }
        if ('video' !== $form->getName()) {
            $this->initializeLocales($configuration, $entity, $website);
        }
    }

    /**
     * Set uploaded Media.
     */
    private function setUploadedMediaMediaRelation(UploadedFile $uploadedFile, mixed $mediaRelation, Media\Media $media, Website $website): void
    {
        $isUpload = $this->uploader->upload($uploadedFile, $website);

        if ($isUpload) {
            /* Change media on updated (Except in Media library) */
            if (!empty($this->uploader->getFilename()) && $media->getFilename() !== $this->uploader->getFilename()) {
                $oldMedia = $media;
                $media = new Media\Media();
                $media->setWebsite($website);
                $media->setCategory($oldMedia->getCategory());
                $media->setFolder($oldMedia->getFolder());
                $media->setScreen($oldMedia->getScreen());
                $mediaRelation->setMedia($media);
                $this->entityManager->persist($oldMedia);
                $this->setIntlMedia($mediaRelation, $media);
                foreach ($oldMedia->getMediaScreens() as $screen) {
                    $screen->setMedia($media);
                    $this->entityManager->persist($screen);
                }
            }
            $media->setFilename($this->uploader->getFilename());
            $media->setName($this->uploader->getName());
            $media->setExtension($this->uploader->getExtension());
            /* Remove Media if filename is empty */
            if (!$media->getFilename()) {
                $mediaRelation->setMedia(null);
                $this->entityManager->remove($media);
            }
        }
    }

    /**
     * Single Uploaded locale file.
     */
    private function singleUploadedLocaleFile(FormInterface $form, Website $website, mixed $entity): void
    {
        $mediaRelation = $form['mediaRelation']->getData();
        $media = $mediaRelation->getMedia();
        $uploadedFile = $form['mediaRelation']['media']['uploadedFile']->getData();
        $locale = property_exists($entity, 'locale')
            ? $entity->getLocale() : $this->request->get('entitylocale');

        if (!$mediaRelation->getLocale()) {
            $mediaRelation->setLocale($locale);
        }

        if (!$media->getWebsite()) {
            $media->setWebsite($website);
        }

        if ($uploadedFile) {
            $isUpload = $this->uploader->upload($uploadedFile, $website);

            if ($isUpload) {
                /* Change media on updated */
                if (!empty($media->getFilename()) && $media->getFilename() !== $this->uploader->getFilename()) {
                    $oldMedia = $media;
                    $media = new Media\Media();
                    $media->setWebsite($website);
                    $media->setCategory($oldMedia->getCategory());
                    $media->setFolder($oldMedia->getFolder());
                    $mediaRelation->setMedia($media);
                }
                $media->setExtension($this->uploader->getExtension());
                $media->setFilename($this->uploader->getFilename());
                $media->setName($this->uploader->getName());
            }
        }
    }

    /**
     * Update Media.
     */
    private function setUploadedMedia(UploadedFile $uploadedFile, Media\Media $media, Website $website): void
    {
        if ($media->getFilename()) {
            $this->uploader->removeFile($media->getFilename());
        }

        $isUpload = $this->uploader->upload($uploadedFile, $website);

        if ($isUpload) {
            $media->setFilename($this->uploader->getFilename());
            $media->setName($this->uploader->getName());
            $media->setExtension($this->uploader->getExtension());
        }
    }

    /**
     * Initialize locales MediaRelation & Media WebsiteModel.
     */
    private function initializeLocales(Configuration $configuration, mixed $entity, Website $website): void
    {
        $defaultLocaleMedia = null;
        foreach ($entity->getMediaRelations() as $mediaRelation) {
            /* Get default locale Media */
            if ($mediaRelation->getLocale() === $configuration->getLocale()) {
                $defaultLocaleMedia = $mediaRelation->getMedia();
            }

            /** Set Media WebsiteModel */
            $media = !$mediaRelation->getMedia() ? new Media\Media() : $mediaRelation->getMedia();
            if (!$media->getWebsite()) {
                $media->setWebsite($website);
            }

            $intl = $mediaRelation->getIntl();
            if ($intl && !$intl->getLocale()) {
                $intl->setLocale($mediaRelation->getLocale());
            }
        }

        /* Set others locales Medias if is new and Media empty */
        foreach ($entity->getMediaRelations() as $mediaRelation) {
            if (!$mediaRelation->isInit() && $mediaRelation->getLocale() !== $configuration->getLocale() && $defaultLocaleMedia && !$mediaRelation->getMedia()->getFilename()) {
                $media = $mediaRelation->getMedia();
                if ($media->getId()) {
                    $this->entityManager->remove($media);
                }
                $mediaRelation->setMedia($defaultLocaleMedia);
                $mediaRelation->setInit(true);
            }
        }
    }

    /**
     * Set MediaRelation with entitylocale.
     */
    public function setEntityLocale(array $interface, mixed $entity, ?Website $website = null): void
    {
        if (!$entity->getMediaRelation()) {
            $locale = empty($this->request->get('entitylocale')) ? $website->getConfiguration()->getLocale() : $this->request->get('entitylocale');
            $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelation');
            $mediaRelation = new ($mediaRelationData->targetEntity)();
            $mediaRelation->setLocale($locale);
            $mediaRelation->setCategorySlug($interface['name']);
            $entity->setMediaRelation($mediaRelation);
            $this->entityManager->persist($entity);
            if (!$this->request->isMethod('post')) {
                $this->entityManager->flush();
                try {
                    $this->entityManager->refresh($entity);
                } catch (Exception $exception) {
                }
            }
        }
    }

    /**
     * Synchronize locales MediaRelation[].
     *
     * @throws NonUniqueResultException
     */
    public function setMediaRelations(mixed $entity, Website $website, array $interface = [], bool $setFirst = true, bool $force = false): void
    {
        $asMultiple = !empty($interface['configuration']) && property_exists($interface['configuration'], 'mediaMulti')
            ? $interface['configuration']->mediaMulti : false;

        if (!$asMultiple && method_exists($entity, 'getMediaRelations') && !$entity instanceof Media\Media || $force) {

            $mediasRelations = $entity->getMediaRelations();

            if (0 === $mediasRelations->count() && $setFirst) {
                foreach ($website->getConfiguration()->getAllLocales() as $locale) {
                    $media = new Media\Media();
                    $media->setWebsite($website);
                    $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
                    $mediaRelation = new ($mediaRelationData->targetEntity)();
                    $mediaRelation->setLocale($locale);
                    $mediaRelation->setMedia($media);
                    $entity->addMediaRelation($mediaRelation);
                    $this->setIntlMedia($mediaRelation, $media);
                }
                $this->entityManager->persist($entity);
                if (!$this->request->isMethod('post')) {
                    $this->entityManager->flush();
                }
            }

            foreach ($mediasRelations as $mediaRelation) {
                $this->synchronizeLocales($website, $interface, $entity, $mediaRelation);
            }

            if ($entity instanceof Layout\Block) {
                if ($website->getConfiguration()->isMediasSecondary()) {
                    $localesMedias = [];
                    foreach ($entity->getMediaRelations() as $mediaRelation) {
                        $localesMedias[$mediaRelation->getLocale()][] = $mediaRelation;
                    }
                    foreach ($website->getConfiguration()->getAllLocales() as $locale) {
                        if (!empty($localesMedias[$locale]) && count($localesMedias[$locale]) > 2) {
                            foreach ($localesMedias[$locale] as $key => $mediaRelation) {
                                $position = $key + 1;
                                if ($position > 2) {
                                    $entity->removeMediaRelation($mediaRelation);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Set Locale MediaRelation.
     *
     * @throws NonUniqueResultException
     */
    private function setEntity(array $interface, string $locale, mixed $entity, mixed $mediaRelation): void
    {
        if (!empty($interface['name'])) {

            $classname = $this->entityManager->getClassMetadata(get_class($entity))->getName();
            $repository = $this->entityManager->getRepository($classname);
            $entityLocaleRelation = $repository->createQueryBuilder('e')->select('e')
                ->leftJoin('e.mediaRelations', 'm')
                ->andWhere('e.id = :id')
                ->andWhere('m.position = :position')
                ->andWhere('m.locale = :locale')
                ->setParameter('position', $mediaRelation->getPosition())
                ->setParameter('locale', $locale)
                ->setParameter('id', $entity->getId())
                ->addSelect('m')
                ->orderBy('m.locale', 'ASC')
                ->getQuery()
                ->getOneOrNullResult();

            $localeRelation = $entityLocaleRelation && !$entityLocaleRelation->getMediaRelations()->isEmpty()
                ? $entityLocaleRelation->getMediaRelations()[0] : null;

            if (!$localeRelation && !in_array($locale, $this->localesSet)) {
                $this->localesSet[] = $locale;
                $media = $mediaRelation->getMedia();
                $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
                $localeRelation = new ($mediaRelationData->targetEntity)();
                $localeRelation->setLocale($locale);
                $localeRelation->setPosition($mediaRelation->getPosition());
                $localeRelation->setMedia($mediaRelation->getMedia());
                $localeRelation->setCategorySlug($mediaRelation->getCategorySlug());
                $entity->addMediaRelation($localeRelation);
                if (!empty($media)) {
                    $this->setIntlMedia($localeRelation, $media);
                }
            }

            if (!empty($entity)) {
                $this->entityManager->persist($entity);
                if (!$this->request->isMethod('post')) {
                    $this->entityManager->flush();
                }
            }

            try {
                $this->entityManager->refresh($entity);
            } catch (Exception $exception) {
            }
        }
    }

    /**
     * Set intl MediaRelation.
     */
    private function setIntlMedia(mixed $mediaRelation, Media\Media $media): void
    {
        if (!$this->request->isMethod('post')) {
            $locale = $mediaRelation->getLocale();
            $existing = false;
            foreach ($media->getIntls() as $intl) {
                if ($intl->getLocale() === $locale) {
                    $existing = true;
                    break;
                }
            }
            if (!$existing) {
                $intl = new Media\MediaIntl();
                $intl->setLocale($mediaRelation->getLocale());
                $intl->setTitle($media->getName());
                $intl->setWebsite($media->getWebsite());
                $media->addIntl($intl);
            }
        } elseif ($mediaRelation->getIntl() && !$mediaRelation->getIntl()->getWebsite()) {
            $intl = $mediaRelation->getIntl();
            $intl->setWebsite($this->coreLocator->website()->entity);
        }
    }

    /**
     * Set Media.
     */
    private function setMedia(Media\Media $media): void
    {
        $dbName = str_replace('.'.$media->getExtension(), '', $media->getFilename());
        if ($media->getName() !== $dbName) {
            $isRename = $this->uploader->rename($dbName, $media->getName(), $media->getExtension());
            if ($isRename) {
                $media->setFilename($media->getName().'.'.$media->getExtension());
            }
        }
    }

    /**
     * Remove Media & files.
     *
     * @throws NonUniqueResultException
     */
    public function removeMedia(Media\Media $media): object
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $removeScreenMedias = true;
        foreach ($metadata as $data) {
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $classname = $data->getName();
                $entity = new $classname();
                if (method_exists($entity, 'getMedia') && BaseMediaRelation::class !== $classname) {
                    $metadata = $this->entityManager->getClassMetadata(get_class($entity));
                    $metadata = $metadata->getAssociationMappings();
                    $asMedia = !empty($metadata['media']['targetEntity']) && Media\Media::class === $metadata['media']['targetEntity'];
                    if ($asMedia) {
                        $associatedMediaRelations = $this->entityManager->getRepository($classname)
                            ->createQueryBuilder('mr')->select('mr')
                            ->andWhere('mr.media = :media')
                            ->setParameter('media', $media)
                            ->getQuery()
                            ->getResult();
                        if ($associatedMediaRelations) {
                            $removeScreenMedias = false;
                            break;
                        }
                    }
                }
            }
        }

        if ($removeScreenMedias) {
            foreach ($media->getMediaScreens() as $mediaScreen) {
                $this->uploader->removeFile($mediaScreen->getFilename());
                $this->entityManager->remove($mediaScreen);
            }
        }

        $messageInfo = $this->removeMediaMessages($media);

        if ($messageInfo->deletable) {
            $this->uploader->removeFile($media->getFilename());
            $this->entityManager->remove($media);
            if (!$this->request->isMethod('post')) {
                $this->entityManager->flush();
            }
        }

        return (object) [
            'success' => $messageInfo->deletable,
            'message' => $messageInfo->message,
        ];
    }

    /**
     * Get Media message alert.
     *
     * @throws NonUniqueResultException
     */
    private function removeMediaMessages(Media\Media $media): object
    {
        $excludes = [Website::class, Media\MediaRelation::class, Media\Media::class, Media\Thumb::class];
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $message = $this->translator->trans('Suppression impossible pour le fichier suivant :', [], 'admin').' '.$media->getFilename();
        $message .= '<ul class="mb-0 text-italic">';
        $deletable = true;

        foreach ($metadata as $data) {
            $classname = $data->getName();
            $execute = 0 === $data->getReflectionClass()->getModifiers();
            if ($execute && !in_array($classname, $excludes) && !str_contains($classname, 'MediaRelation')) {
                $entity = new $classname();
                if (method_exists($entity, 'getMediaRelations') || method_exists($entity, 'getMediaRelation')) {
                    $identifier = method_exists($entity, 'getMediaRelations') ? 'mediaRelations' : 'mediaRelation';
                    $repository = $this->entityManager->getRepository($classname);
                    $existing = $repository->createQueryBuilder('e')->select('e')
                        ->leftJoin('e.'.$identifier, 'mr')
                        ->andWhere('mr.media = :media')
                        ->setParameter('media', $media)
                        ->addSelect('mr')
                        ->getQuery()
                        ->getResult();
                    foreach ($existing as $mappingEntity) {
                        $deletable = false;
                        $message .= $this->removeMediaMessage($classname, $mappingEntity, $media, $existing);
                    }
                }
            }
        }

        $message .= '</ul>';

        return (object) [
            'deletable' => $deletable,
            'message' => $deletable ? '' : $message,
        ];
    }

    /**
     * Get message.
     *
     * @throws NonUniqueResultException
     */
    private function removeMediaMessage(string $classname, mixed $entity, Media\Media $media, mixed $existing = null): string
    {
        $message = '';
        $interface = $this->interfaceHelper->generate($classname);
        $layoutAdminName = $this->getLayoutAdminName($entity);
        $localesMessages = $this->getRemoveMediaMessageLocales($media, $existing);
        $masterEntityMessage = $this->getRemoveMediaMessageMasterEntityMessage($entity);

        if (method_exists($entity, 'getAdminName') && $entity->getAdminName()) {
            if ($layoutAdminName && $entity instanceof Layout\Block) {
                $message = '<li>'.$entity->getAdminName().' - ['.$this->translator->trans('Mise en page :', [], 'admin').' '.$layoutAdminName.'] - ['.$this->translator->trans('Bloc :', [], 'admin').' '.$entity->getBlockType()->getAdminName().' (ID: '.$entity->getId().')] - '.$masterEntityMessage.$localesMessages.'</li>';
            } elseif ($layoutAdminName) {
                $message = '<li>'.$entity->getAdminName().' - ['.$this->translator->trans('Mise en page :', [], 'admin').''.$layoutAdminName.'] - '.$masterEntityMessage.$localesMessages.'</li>';
            } else {
                $message = '<li>'.$entity->getAdminName().$masterEntityMessage.$localesMessages.'</li>';
            }
        } elseif ($entity instanceof Seo) {
            $message = '<li>'.$this->translator->trans('Image de partage', [], 'admin').'</li>';
        } elseif (method_exists($entity, 'getIntls')) {
            foreach ($entity->getIntls() as $intl) {
                if (!empty($intl->getTitle()) && $intl->getLocale() === $media->getWebsite()->getConfiguration()->getLocale()) {
                    $message = '<li>'.$intl->getTitle().$masterEntityMessage.$localesMessages.'</li>';
                }
            }
        }

        if (!$message) {
            $translation = $this->translator->trans('singular', [], 'entity_'.$interface['name']);
            $singular = 'singular' != $translation ? $translation : ucfirst($interface['name']);
            if ($layoutAdminName && $entity instanceof Layout\Block) {
                $message = '<li>'.$this->translator->trans('Utilisé dans Bloc', [], 'admin').' '.$entity->getBlockType()->getAdminName().' (ID : '.$entity->getId().') - '.$this->translator->trans('Mise en page :', [], 'admin').' '.$layoutAdminName.$masterEntityMessage.$localesMessages.'</li>';
            } elseif ($layoutAdminName) {
                $message = '<li>'.$this->translator->trans('Utilisé dans', [], 'admin').' '.$singular.' (ID : '.$entity->getId().') - '.$this->translator->trans('Mise en page :', [], 'admin').' '.$layoutAdminName.$masterEntityMessage.$localesMessages.'</li>';
            } else {
                $message = '<li>'.$this->translator->trans('Utilisé dans', [], 'admin').' '.$singular.' (ID : '.$entity->getId().')'.$masterEntityMessage.$localesMessages.'</li>';
            }
        }

        return mb_convert_encoding($message, 'UTF-8', 'UTF-8');
    }

    /**
     * Get message for locale.
     */
    private function getRemoveMediaMessageLocales(Media\Media $media, mixed $existing = null): ?string
    {
        $locales = $media->getWebsite()->getConfiguration()->getAllLocales();
        $localesMessages = '';
        if (count($locales) > 1) {
            if (is_iterable($existing)) {
                $locales = [];
                foreach ($existing as $existingEntity) {
                    if (method_exists($existingEntity, 'getMediaRelations')) {
                        foreach ($existingEntity->getMediaRelations() as $mediaRelation) {
                            if (!in_array($mediaRelation->getLocale(), $locales)) {
                                $localesMessages .= ' <img src="/medias/icons/flags/'.$mediaRelation->getLocale().'.svg" width="19" height="14">';
                                $locales[] = $mediaRelation->getLocale();
                            }
                        }
                    }
                }
            }
        }

        return $localesMessages ? ' - [Langue(s) : '.$localesMessages.']' : '';
    }

    /**
     * Get master entity message remove.
     *
     * @throws NonUniqueResultException
     */
    private function getRemoveMediaMessageMasterEntityMessage($entity): ?string
    {
        $masterEntityMessage = '';

        if ($entity instanceof Layout\Block) {
            $layout = $entity->getCol()->getZone()->getLayout();
            $metasData = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $masterEntity = null;

            foreach ($metasData as $metadata) {
                $metadataClassname = $metadata->getName();
                $baseEntity = 0 === $metadata->getReflectionClass()->getModifiers() ? new $metadataClassname() : null;
                if (Layout\Zone::class !== $metadataClassname && $baseEntity && method_exists($baseEntity, 'getLayout')) {
                    $masterEntity = $this->entityManager->getRepository($metadataClassname)->createQueryBuilder('e')
                        ->leftJoin('e.layout', 'l')
                        ->andWhere('e.layout = :layout')
                        ->setParameter('layout', $layout)
                        ->addSelect('l')
                        ->getQuery()
                        ->getOneOrNullResult();
                    if ($masterEntity) {
                        $this->entityManager->getUnitOfWork()->markReadOnly($masterEntity);
                        break;
                    }
                }
            }

            if ($masterEntity instanceof Layout\Page) {
                $masterEntityMessage = ' [Page : '.$layout->getAdminName().' ID: '.$masterEntity->getId().']';
            }
            if ($masterEntity instanceof \App\Entity\Module\Newscast\Newscast) {
                $masterEntityMessage = ' [Actualité : '.$layout->getAdminName().' ID: '.$masterEntity->getId().']';
            }
            if ($masterEntity instanceof \App\Entity\Module\Form\Form) {
                $masterEntityMessage = ' [Formulaire : '.$layout->getAdminName().' ID: '.$masterEntity->getId().']';
            }
            if ($masterEntity instanceof \App\Entity\Module\Newscast\Category) {
                $masterEntityMessage = " - [ Catégorie d'Actualité : ".$layout->getAdminName().' ID: '.$masterEntity->getId().']';
            }
        }

        return $masterEntityMessage;
    }

    /**
     * Get layout link.
     */
    private function getLayoutAdminName($entity): ?string
    {
        $layout = null;
        if ($entity instanceof Layout\Zone) {
            $layout = $entity->getLayout();
        }
        if ($entity instanceof Layout\Block) {
            $layout = $entity->getCol()->getZone()->getLayout();
        }
        $adminName = $layout instanceof Layout\Layout ? preg_replace('/\x03/', ' ', $layout->getAdminName()) : null;

        return $adminName ? ucfirst(strtolower($adminName)) : null;
    }

    /**
     * To set GeoJson file.
     */
    private function setGeoJson(mixed $entity, FormInterface $form, Website $website): void
    {
        if ($entity instanceof Point && !empty($form['geoJson'])) {
            $geoJson = $form['geoJson']->getData();
            $geoJson->setPoint($entity);
            $geoJson->setLocale('all');
            $geoJsonMedia = $geoJson->getMedia();
            $geoJsonMedia->setWebsite($website);
            if (!empty($this->request->files->get('point')['geoJson']['media']['uploadedFile'])) {
                $folder = $this->entityManager->getRepository(Media\Folder::class)->findOneBy(['website' => $website, 'slug' => 'geojson']);
                if (!$folder instanceof Media\Folder) {
                    $folderPosition = count($this->entityManager->getRepository(Media\Folder::class)->findBy(['website' => $website, 'level' => 1])) + 1;
                    $folder = new Media\Folder();
                    $folder->setWebsite($website);
                    $folder->setPosition($folderPosition);
                    $folder->setAdminName('GeoJson');
                    $folder->setSlug('geojson');
                    $this->entityManager->persist($folder);
                }
                $geoJsonMedia->setFolder($folder);
                $uploadedFile = $this->request->files->get('point')['geoJson']['media']['uploadedFile'];
                $this->setUploadedMedia($uploadedFile, $geoJsonMedia, $website);
            }
        }
    }
}
