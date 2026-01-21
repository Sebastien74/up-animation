<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core\Website;
use App\Entity\Media\Media;
use App\Entity\Security\UserFront;
use App\Entity\Seo\Url;
use App\Form\Manager\Seo\UrlManager;
use App\Service\Core\InterfaceHelper;
use App\Service\Core\Uploader;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * BaseManager.
 *
 * Manage form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BaseManager::class, 'key' => 'core_base_form_manager'],
])]
class BaseManager
{
    private bool $inAdmin;

    /**
     * BaseManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly UrlManager $urlManager,
        private readonly Uploader $uploader,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->inAdmin = (bool) preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $coreLocator->requestStack()->getMainRequest()->getUri());
    }

    /**
     * @prePersist
     *
     * @throws MappingException
     */
    public function prePersist(mixed $entity, Website $website, ?UserFront $userFront = null): void
    {
        $allLocales = $this->getAllLocales($website);
        $intls = $entity->getIntls();
        if (method_exists($entity, 'setUserFront')) {
            $entity->setUserFront($userFront);
        }
        if (method_exists($entity, 'setWebsite')) {
            $entity->setWebsite($website);
        }
        $this->addIntls($allLocales, $website, $intls, $entity);
        $this->addUrls($allLocales, $website, $entity);
        $this->setTitleForce($intls);
    }

    /**
     * Front post.
     *
     * @throws NonUniqueResultException
     */
    public function frontPost(mixed $entity, ?UploadedFile $uploadedFile = null): void
    {
        $userFront = $entity->getUserFront();

        $this->setFile($userFront, $entity, $uploadedFile);
        $this->setUrls($entity);
        $this->setAdminName($entity);

        if (!$entity->getId()) {
            $this->setPosition($entity);
        }

        if (!$entity->getId() && $userFront instanceof UserFront) {
            $entity->setAuthor($userFront->getLastName().' '.$userFront->getFirstName());
        }

        $this->coreLocator->em()->persist($entity);
        $this->coreLocator->em()->flush();
    }

    /**
     * Set videos.
     *
     * @throws NonUniqueResultException
     * @throws MappingException
     */
    public function setVideos(Website $website, string $classname, mixed $entity, array $interface): void
    {
        $interfaceVideo = $this->interfaceHelper->generate($classname);
        $masterField = !empty($interfaceVideo['masterField']) ? $interfaceVideo['masterField'] : $interface['name'];
        $position = count($this->coreLocator->em()->getRepository($classname)->findBy([$masterField => $entity])) + 1;
        $allLocales = $this->getAllLocales($website);

        foreach ($allLocales as $locale) {
            foreach ($entity->getVideos() as $video) {
                $existing = false;
                foreach ($video->getIntls() as $intl) {
                    if ($intl->getLocale() === $locale) {
                        $existing = true;
                        break;
                    }
                }
                if (!$existing) {
                    $intlData = $this->coreLocator->metadata($video, 'intls');
                    $intl = new ($intlData->targetEntity)();
                    $intl->setLocale($locale);
                    $intl->setVideo($video->getAdminName());
                    $intl->setWebsite($website);
                    if (method_exists($intl, $intlData->setter)) {
                        $setter = $intlData->setter;
                        $intl->$setter($video);
                    }
                    $video->addIntl($intl);
                }
                if (!$video->getPosition()) {
                    $video->setPosition($position);
                    ++$position;
                }
                $this->coreLocator->em()->persist($video);
            }
        }
    }

    /**
     * Add intls.
     *
     * @throws MappingException
     */
    private function addIntls(array $allLocales, Website $website, Collection $intls, mixed $entity): void
    {
        foreach ($allLocales as $locale) {
            $existing = $this->existingLocale($locale, $intls);
            if (!$existing) {
                $intlData = $this->coreLocator->metadata($entity, 'intls');
                $intl = new ($intlData->targetEntity)();
                $intl->setLocale($locale);
                $intl->setWebsite($website);
                if (method_exists($intl, $intlData->setter)) {
                    $setter = $intlData->setter;
                    $intl->$setter($entity);
                }
                $entity->addIntl($intl);
                $this->coreLocator->em()->persist($entity);
            }
        }
    }

    /**
     * Add Urls.
     */
    private function addUrls(array $allLocales, Website $website, mixed $entity): void
    {
        $urls = $entity->getUrls();
        foreach ($allLocales as $locale) {
            $existing = $this->existingLocale($locale, $urls);
            if (!$existing) {
                $url = new Url();
                $url->setLocale($locale);
                $url->setWebsite($website);
                $entity->addUrl($url);
                $this->coreLocator->em()->persist($entity);
            }
        }
    }

    /**
     * Set title force to H1.
     */
    public function setTitleForce(Collection $intls): void
    {
        foreach ($intls as $intl) {
            $intl->setTitleForce(1);
        }
    }

    /**
     * Check if entity by locale existing.
     */
    private function existingLocale(string $locale, Collection $collection): bool
    {
        foreach ($collection as $entity) {
            if ($entity->getLocale() === $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set File.
     *
     * @throws MappingException
     */
    private function setFile(UserFront $user, mixed $entity, ?UploadedFile $uploadedFile = null): void
    {
        if ($uploadedFile instanceof UploadedFile) {
            $website = $entity->getWebsite();
            $isUpload = $this->uploader->upload($uploadedFile, $website, null, false);

            $media = new Media();
            foreach ($entity->getMediaRelations() as $mediaRelation) {
                if ($mediaRelation->getMedia()->getUserFront() instanceof UserFront) {
                    $media = $mediaRelation->getMedia();
                    break;
                }
            }

            if ($media->getFilename()) {
                $dirname = $this->uploader->getUploadsPath().'/'.$media->getFilename();
                $filesystem = new Filesystem();
                if ($filesystem->exists($dirname) && !is_dir($dirname)) {
                    $filesystem->remove($dirname);
                }
            }

            if ($isUpload) {
                $media->setFilename($this->uploader->getFilename());
                $media->setName($this->uploader->getName());
                $media->setExtension($this->uploader->getExtension());
            }

            if (!$media->getId()) {
                $media->setWebsite($website);
                $media->setUserFront($user);
                $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
                $mediaRelation = new ($mediaRelationData->targetEntity)();
                $mediaRelation->setLocale($website->getConfiguration()->getLocale());
                $mediaRelation->setMedia($media);
                $entity->addMediaRelation($mediaRelation);
            }
        }
    }

    /**
     * Set Urls.
     *
     * @throws NonUniqueResultException
     */
    private function setUrls(mixed $entity): void
    {
        $titles = [];
        foreach ($entity->getIntls() as $intl) {
            $titles[$intl->getLocale()] = $intl->getTitle();
        }

        foreach ($entity->getUrls() as $url) {
            if (!$url->getCode()) {
                $url->setCode(Urlizer::urlize($titles[$url->getLocale()]));
                $existing = $this->urlManager->getExistingUrl($url, $url->getWebsite(), $entity);
                $stringUrl = !$existing ? $url->getCode() : $url->getCode().'-'.$entity->getId();
                $url->setCode($stringUrl);
            }
            if (!$this->inAdmin) {
                $url->setOnline(false);
            }
        }
    }

    /**
     * Set adminName.
     */
    private function setAdminName(mixed $entity): void
    {
        $defaultLocale = $entity->getWebsite()->getConfiguration()->getLocale();
        $adminName = null;
        foreach ($entity->getIntls() as $intl) {
            if ($intl->getLocale() === $defaultLocale) {
                $adminName = $intl->getTitle();
                break;
            }
        }
        $entity->setAdminName($adminName);
    }

    /**
     * To set position.
     */
    private function setPosition(mixed $entity): void
    {
        $position = count($this->coreLocator->em()->getRepository(get_class($entity))->findBy(['website' => $entity->getWebsite()])) + 1;
        $entity->setPosition($position);
    }

    /**
     * To get all locales.
     */
    private function getAllLocales(Website $website): ?array
    {
        return $website->getConfiguration()->getAllLocales();
    }
}
