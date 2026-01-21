<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Website;
use App\Entity\Media;
use App\Service\Core\Uploader;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * BaseDuplicateManager.
 *
 * Manage admin form duplication
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BaseDuplicateManager::class, 'key' => 'core_base_duplication_form_manager'],
])]
class BaseDuplicateManager
{
    private const bool DISABLE_DUPLICATION_MEDIA = false;

    private ?Request $request;
    protected ?Website $website = null;

    /**
     * BaseDuplicateManager constructor.
     */
    public function __construct(
        protected string $projectDir,
        protected CoreLocatorInterface $coreLocator,
        protected EntityManagerInterface $entityManager,
        protected Uploader $uploader,
        protected RequestStack $requestStack,
    ) {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Duplicate MediaRelations.
     */
    protected function addMediaRelations(mixed $entity, Collection $mediaRelationsToDuplicate): void
    {
        $session = new Session();
        $duplicateToWebsiteSession = $session->get('DUPLICATE_TO_WEBSITE') ? $session->get('DUPLICATE_TO_WEBSITE') : $this->entityManager->getRepository(Website::class)->find($this->request->get('website'));
        $duplicateToWebsite = $duplicateToWebsiteSession instanceof Website ? $duplicateToWebsiteSession->getConfiguration()->isDuplicateMediasStatus() : self::DISABLE_DUPLICATION_MEDIA;
        $duplicateToWebsiteFromZoneSession = $session->get('DUPLICATE_TO_WEBSITE_FROM_ZONE');
        $duplicateToWebsiteFromZone = $duplicateToWebsiteFromZoneSession instanceof Website ? $duplicateToWebsiteFromZoneSession->getConfiguration()->isDuplicateMediasStatus() : self::DISABLE_DUPLICATION_MEDIA;

        if ($duplicateToWebsite || $duplicateToWebsiteFromZone) {
            foreach ($mediaRelationsToDuplicate as $mediaRelationToDuplicate) {
                /** @var Media\MediaRelation $mediaRelationToDuplicate */
                $mediaToDuplicate = $mediaRelationToDuplicate->getMedia();
                $referWebsite = $mediaToDuplicate instanceof Media\Media ? $mediaToDuplicate->getWebsite() : $this->coreLocator->website()->entity;
                $sameSite = $referWebsite instanceof Website && $duplicateToWebsiteSession->getId() === $referWebsite->getId();
                $locale = $sameSite ? $mediaRelationToDuplicate->getLocale() : $duplicateToWebsiteSession->getConfiguration()->getLocale();
                $allowed = $sameSite || $mediaRelationToDuplicate->getLocale() === $referWebsite->getConfiguration()->getLocale();

                if ($allowed && $mediaRelationToDuplicate->getMedia() && 'poster' !== $mediaRelationToDuplicate->getMedia()->getScreen()) {
                    $mediaClassname = $this->coreLocator->metadata($entity, 'mediaRelations')->targetEntity;
                    $mediaRelation = new $mediaClassname();
                    $mediaRelation->setLocale($locale);
                    $mediaRelation->setCategorySlug($mediaRelationToDuplicate->getCategorySlug());
                    $mediaRelation->setPopup($mediaRelationToDuplicate->isPopup());
                    $mediaRelation->setMaxWidth($mediaRelationToDuplicate->getMaxWidth());
                    $mediaRelation->setMaxHeight($mediaRelationToDuplicate->getMaxHeight());
                    $mediaRelation->setPosition($mediaRelationToDuplicate->getPosition());
                    $mediaRelation->setDownloadable($mediaRelationToDuplicate->isDownloadable());

                    if (!$sameSite) {
                        $path = $this->projectDir.'/public/uploads/'.$referWebsite->getUploadDirname().'/'.$mediaToDuplicate->getFilename();
                        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
                        if ($mediaToDuplicate->getFilename()) {
                            $uploadedFile = $this->uploader->pathToUploadedFile($path);
                            if ($uploadedFile) {
                                $this->uploader->upload($uploadedFile, $duplicateToWebsiteSession);
                                $media = $this->setMedia($mediaToDuplicate, $duplicateToWebsiteSession);
                                foreach ($mediaToDuplicate->getMediaScreens() as $mediaScreen) {
                                    $mediaScreen = $this->setMedia($mediaScreen, $duplicateToWebsiteSession);
                                    $media->addMediaScreen($mediaScreen);
                                }
                                $mediaRelation->setMedia($media);
                            }
                        }
                    } else {
                        $mediaRelation->setMedia($mediaRelationToDuplicate->getMedia());
                    }

                    $entity->addMediaRelation($mediaRelation);

                    $intl = $this->addIntl($mediaRelation, $mediaRelation->getLocale(), $mediaRelationToDuplicate->getIntl());
                    $mediaRelation->setIntl($intl);

                    $this->entityManager->persist($entity);
                }
            }
        }
    }

    /**
     * To duplicate Media.
     */
    protected function setMedia(?Media\Media $mediaToDuplicate = null, ?Website $duplicateToWebsiteSession = null): Media\Media
    {
        $media = new Media\Media();
        $media->setWebsite($duplicateToWebsiteSession);
        $media->setCategory($mediaToDuplicate->getCategory());
        $media->setName($mediaToDuplicate->getName());
        $media->setScreen($mediaToDuplicate->getScreen());
        $media->setCopyright($mediaToDuplicate->getCopyright());
        $media->setExtension($mediaToDuplicate->getExtension());
        $media->setFilename($mediaToDuplicate->getFilename());
        $media->setHideHover($mediaToDuplicate->isHideHover());
        $media->setNotContractual($mediaToDuplicate->isNotContractual());
        $media->setQuality($mediaToDuplicate->getQuality());
        $media->setTitlePosition($mediaToDuplicate->getTitlePosition());
        $media->setFolder($this->setFolder($mediaToDuplicate->getFolder(), $duplicateToWebsiteSession));

        return $media;
    }

    /**
     * To duplicate Folder.
     */
    protected function setFolder(?Media\Folder $folderToDuplicate = null, ?Website $duplicateToWebsiteSession = null): ?Media\Folder
    {
        $folder = null;
        if ($folderToDuplicate instanceof Media\Folder && $duplicateToWebsiteSession instanceof Website) {
            $folder = $this->entityManager->getRepository(Media\Folder::class)->findOneBy([
                'website' => $duplicateToWebsiteSession,
                'slug' => $folderToDuplicate->getSlug(),
            ]);
            if (!$folder) {
                $folder = new Media\Folder();
                $folder->setWebsite($duplicateToWebsiteSession);
                $folder->setSlug($folderToDuplicate->getSlug());
                $folder->setPosition($folderToDuplicate->getPosition());
                $folder->setAdminName($folderToDuplicate->getAdminName());
                $folder->setLevel($folderToDuplicate->getLevel());
                $folder->setDeletable($folderToDuplicate->isDeletable());
                $folder->setWebmaster($folderToDuplicate->isWebmaster());
                $this->entityManager->persist($folder);
                $parent = $folderToDuplicate->getParent();
                if ($parent instanceof Media\Folder) {
                    $folder->setParent($this->setFolder($parent, $duplicateToWebsiteSession));
                }
            }
        }

        return $folder;
    }

    /**
     * Duplicate by properties.
     *
     * @throws Exception
     */
    protected function setByProperties(mixed $newEntity, mixed $entityToDuplicate, array $mappingsFields = [], bool $force = false): mixed
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($newEntity));
        $configFields = ['id' => 'exclude', 'createdAt' => 'newDate', 'updatedAt' => 'exclude'];

        foreach ($metadata->getFieldNames() as $name) {
            if (!isset($configFields[$name]) || 'exclude' !== $configFields[$name]) {
                $getMethod = 'get'.ucfirst($name);
                $isMethod = 'is'.ucfirst($name);
                $setter = 'set'.ucfirst($name);
                $value = method_exists($entityToDuplicate, $getMethod) ? $entityToDuplicate->$getMethod() : $entityToDuplicate->$isMethod();
                if (isset($configFields[$name]) && 'newDate' === $configFields[$name]) {
                    $value = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
                } elseif ('computeETag' === $name) {
                    $date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
                    $value = uniqid().md5($date->format('YmdHis'));
                }
                $newEntity->$setter($value);
            }
        }

        $mappingsFields = array_merge($mappingsFields, ['action', 'transition', 'blockType']);
        foreach ($metadata->getAssociationMappings() as $mapping) {
            if (in_array($mapping['fieldName'], $mappingsFields)) {
                $getMethod = 'get'.ucfirst($mapping['fieldName']);
                $isMethod = 'is'.ucfirst($mapping['fieldName']);
                $getter = method_exists($newEntity, $getMethod) ? $getMethod : $isMethod;
                $setter = 'set'.ucfirst($mapping['fieldName']);
                if (method_exists($newEntity, $getter) && method_exists($newEntity, $setter)) {
                    $newEntity->$setter($entityToDuplicate->$getter());
                } else {
                    $adder = str_ends_with($mapping['fieldName'], 'ies') ? 'add'.ucfirst(substr_replace($mapping['fieldName'], 'y', -3))
                        : (str_ends_with($mapping['fieldName'], 's') ? 'add'.ucfirst(rtrim($mapping['fieldName'], 's')) : $mapping['fieldName']);
                    if (method_exists($newEntity, $adder) && method_exists($entityToDuplicate, $getter)) {
                        if ($entityToDuplicate->$getter() instanceof PersistentCollection) {
                            foreach ($entityToDuplicate->$getter() as $itemCollection) {
                                $newEntity->$adder($itemCollection);
                            }
                        }
                    }
                }
            }
        }

        if (method_exists($entityToDuplicate, 'getIntls')) {
            $this->addIntls($newEntity, $entityToDuplicate->getIntls());
        }

        if (method_exists($entityToDuplicate, 'getMediaRelations')) {
            $this->addMediaRelations($newEntity, $entityToDuplicate->getMediaRelations(), $force);
        }

        return $newEntity;
    }

    /**
     * Duplicate intls.
     */
    protected function addIntls(mixed $entity, Collection $intlsToDuplicate): void
    {
        foreach ($intlsToDuplicate as $intlToDuplicate) {
            $intl = $this->addIntl($entity, $intlToDuplicate->getLocale(), $intlToDuplicate);
            $entity->addIntl($intl);
        }
    }

    /**
     * Duplicate intl.
     */
    protected function addIntl(mixed $entity, string $locale, mixed $intlToDuplicate = null): mixed
    {
        $intlData = $this->coreLocator->metadata($entity, 'intls');
        if (!$intlData->targetEntity) {
            $intlData = $this->coreLocator->metadata($entity, 'intl');
        }

        if ($intlData->targetEntity) {

            $intl = new ($intlData->targetEntity)();
            if (!empty($intlToDuplicate)) {
                $this->setByProperties($intl, $intlToDuplicate);
                $intl->setTargetPage($intlToDuplicate->getTargetPage());
            }
            $intl->setLocale($locale);

            $session = new Session();
            $websiteSession = $session->get('DUPLICATE_TO_WEBSITE') ? $session->get('DUPLICATE_TO_WEBSITE')
                : ($session->get('DUPLICATE_TO_WEBSITE_FROM_ZONE') ? $session->get('DUPLICATE_TO_WEBSITE_FROM_ZONE') : null);
            $website = $websiteSession instanceof Website ? $websiteSession : (!empty($intlToDuplicate) ? $intlToDuplicate->getWebsite() : $this->website);
            $intl->setWebsite($website);

            if ($intlData->setter && method_exists($intl, $intlData->setter)) {
                $setter = $intlData->setter;
                $intl->$setter($entity);
            }

            $this->entityManager->persist($intl);

            return $intl;
        }

        return null;
    }
}
