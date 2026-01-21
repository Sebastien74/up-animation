<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Core\Website;
use App\Entity\Layout\Col;
use App\Entity\Layout\Layout;
use App\Entity\Media\Media;
use App\Entity\Media\MediaRelation;
use App\Form\Manager\Media\MediaManager;
use App\Model\Core\WebsiteModel;
use App\Model\ViewModel;
use App\Service\Core\FileInfo;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * MediaRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaRuntime implements RuntimeExtensionInterface
{
    private array $cache = [];

    /**
     * MediaRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FileRuntime $fileRuntime,
        private readonly MediaManager $mediaManager,
    ) {
    }

    /**
     * Get fonts.
     */
    public function fonts(string $fontName): array
    {
        $fonts = [];
        $extensions = ['woff2'];
        $filesystem = new Filesystem();
        $fontName = str_replace('font-', '', $fontName);
        $fontDirname = $this->coreLocator->projectDir().'/assets/lib/fonts/'.ucfirst($fontName);
        $fontDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fontDirname);
        if ($filesystem->exists($fontDirname)) {
            foreach ($extensions as $extension) {
                $finder = Finder::create();
                $finder->in($fontDirname)->name('*.'.$extension);
                foreach ($finder as $file) {
                    if (!$file->isDir() && !$file->getRelativePath()) {
                        $fonts[$extension][] = $file->getFilename();
                    }
                }
            }
        }

        return $fonts;
    }

    /**
     * To define size of all medias in view (Hei).
     *
     * @param string $orientation height or width
     * @param string $type less or more
     */
    public function mediasSize(mixed $entity, string $orientation = 'height', string $type = 'less'): array
    {
        $size = null;
        $sizes = ['width' => null, 'height' => null];
        $website = is_object($entity) && method_exists($entity, 'getWebsite') ? $entity->getWebsite() : null;

        if (is_object($entity) && method_exists($entity, 'getMediaRelations')) {
            $mediaRelations = $entity->getMediaRelations();
            foreach ($mediaRelations as $mediaRelation) {
                /** @var MediaRelation $mediaRelation */
                $media = $mediaRelation->getMedia();
                if (!$website instanceof Website) {
                    $website = $media->getWebsite();
                }
                if ($media instanceof Media) {
                    $fileInfo = $this->fileRuntime->fileInfo($website, $media->getFilename());
                    $infoSize = $fileInfo instanceof FileInfo ? $fileInfo->$orientation() : null;
                    if ($fileInfo instanceof FileInfo) {
                        if (!empty($sizes)) {
                            $size = $infoSize;
                            $sizes = ['width' => $fileInfo->getWidth(), 'height' => $fileInfo->getHeight()];
                        } elseif ('less' === $type && $infoSize < $size) {
                            $size = $infoSize;
                            $sizes = ['width' => $fileInfo->getWidth(), 'height' => $fileInfo->getHeight()];
                        } elseif ('more' === $type && $infoSize > $size) {
                            $size = $infoSize;
                            $sizes = ['width' => $fileInfo->getWidth(), 'height' => $fileInfo->getHeight()];
                        }
                    }
                }
            }
        }

        return $sizes;
    }

    /**
     * Check if entity have main Media.
     */
    public function haveMainMedia(mixed $entity): bool
    {
        if (method_exists($entity, 'getMediaRelations')) {
            $mediaRelations = $entity->getMediaRelations();
            foreach ($mediaRelations as $mediaRelation) {
                /** @var MediaRelation $mediaRelation */
                if ($mediaRelation->isMain() && $mediaRelation->getLocale() === $this->coreLocator->request()->getLocale()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get main Media.
     *
     * @throws NonUniqueResultException
     */
    public function mainMedia(mixed $entity = null, ?string $locale = null, ?string $type = null): ?object
    {
        $locale = $locale ?: $this->coreLocator->request()->getLocale();
        $extensionsTypes = [
            'img' => ['jpg', 'jpeg', 'png', 'svg'],
            'video' => ['mp4', 'webm', 'vtt'],
            'audio' => ['mp3'],
        ];

        if (is_object($entity) && method_exists($entity, 'getMediaRelations') && !$entity->getMediaRelations()->isEmpty()) {
            /** @var Collection $mediaRelations */
            $mediaRelations = $entity->getMediaRelations();
            $localeMediaRelation = null;
            $locale = $locale ?: $this->coreLocator->request()->getLocale();
            foreach ($mediaRelations as $mediaRelation) {
                $asSameLocale = $mediaRelation->getLocale() === $locale;
                /** @var MediaRelation $mediaRelation */
                if ($mediaRelation->isMain() && $asSameLocale && 'img' === $type) {
                    return $mediaRelation;
                }
                $isValid = $asSameLocale && !$localeMediaRelation;
                $media = $mediaRelation->getMedia();
                $extension = $media instanceof Media ? $media->getExtension() : null;
                if (($isValid && !$type) || ($isValid && $type && $extension && in_array($extension, $extensionsTypes[$type]))) {
                    $localeMediaRelation = $mediaRelation;
                }
                $intl = $mediaRelation->getIntl();
                if (($isValid && 'video' === $type && $intl && $intl->getVideo()) || ($isValid && 'video' === $type && 'poster' === $media->getScreen())) {
                    $localeMediaRelation = $mediaRelation;
                }
            }

            return $localeMediaRelation;
        }

        return null;
    }

    /**
     * Get all entity MediaRelation[] group by position.
     *
     * @throws NonUniqueResultException
     */
    public function mediasIdsByPosition(mixed $entity, WebsiteModel $website, array $interface = []): array
    {
        $this->mediaManager->setMediaRelations($entity, $website->entity, $interface, false, true);

        $groups = [];
        $flush = false;

        foreach ($entity->getMediaRelations() as $mediaRelation) {
            if (isset($groups[$mediaRelation->getPosition()][$mediaRelation->getLocale()])) {
                $flush = true;
                $mediaRelation = $this->mediasIdsSetPosition($mediaRelation, $groups);
                $this->coreLocator->em()->persist($mediaRelation);
            }
            $groups[$mediaRelation->getPosition()][$mediaRelation->getLocale()] = $mediaRelation->getId();
        }

        ksort($groups);

        if ($flush) {
            $this->coreLocator->em()->flush();
        }

        return $groups;
    }

    /**
     * To set MediaRelation position if already exist.
     */
    private function mediasIdsSetPosition(mixed $mediaRelation, array $groups): mixed
    {
        $mediaRelation->setPosition($mediaRelation->getPosition() + 1);
        if (isset($groups[$mediaRelation->getPosition()][$mediaRelation->getLocale()])) {
            $this->mediasIdsSetPosition($mediaRelation, $groups);
        }

        return $mediaRelation;
    }

    /**
     * Get all entity MediaRelation[] group by categories.
     */
    public function mediasByCategories(mixed $mediaRelations, bool $main = false): array
    {
        if (!$mediaRelations) {
            return [];
        }

        $groups = [];
        foreach ($mediaRelations as $mediaRelation) {
            /** @var MediaRelation $mediaRelation */
            $media = $mediaRelation->getMedia();
            if (!$main) {
                foreach ($media->getCategories() as $category) {
                    $groups[$category->getSlug()][$mediaRelation->getPosition()] = $mediaRelation;
                    ksort($groups[$category->getSlug()]);
                }
            } else {
                $groups[$media->getCategory()][$mediaRelation->getPosition()] = $mediaRelation;
            }
        }

        ksort($groups);

        return $groups;
    }

    /**
     * Get all entity MediaRelation[] where filename is NOT NULL.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function mediasWithFilename(string $classname, mixed $masterEntity = null): array
    {
        $statement = $this->coreLocator->em()->getRepository($classname)
            ->createQueryBuilder('e')
            ->leftJoin('e.mediaRelations', 'mr')
            ->leftJoin('mr.media', 'm')
            ->andWhere('mr.locale = :locale')
            ->andWhere('m.filename IS NOT NULL')
            ->setParameter('locale', $this->coreLocator->request()->getLocale())
            ->addSelect('mr');

        if (Col::class === $classname && $masterEntity instanceof Layout) {
            $statement->leftJoin('e.zone', 'z')
                ->andWhere('z.layout = :layout')
                ->setParameter('layout', $masterEntity);
        } elseif ($masterEntity) {
            $interface = $this->coreLocator->interfaceHelper()->generate($classname);
            $getter = !empty($interface['masterField']) ? 'get'.ucfirst($interface['masterField']) : false;
            $referClass = new $classname();
            if (is_object($referClass) && $getter && method_exists($referClass, $getter)) {
                $statement->andWhere('e.'.$interface['masterField'].' = :masterEntity')
                    ->setParameter('masterEntity', $masterEntity);
            }
        }

        $result = $statement->getQuery()->getResult();

        $entitiesWithMedia = [];
        foreach ($result as $entity) {
            $entitiesWithMedia[$entity->getId()] = ViewModel::fromEntity($entity, $this->coreLocator)->mainMedia;
        }

        return $entitiesWithMedia;
    }

    /**
     * To check if image is white.
     */
    public function imgIsWhite(?MediaRelation $mediaRelation = null): bool
    {
        if ($mediaRelation instanceof MediaRelation) {
            $media = $mediaRelation->getMedia();
            if ($media instanceof Media) {
                $dirname = $this->coreLocator->projectDir().'/public/uploads/'.$media->getWebsite()->getUploadDirname().'/'.$media->getFilename();
                $size = getimagesize($dirname);
                $width = $size[0];
                $height = $size[1];
                if ($width < 15 && $height < 15) {
                    return true;
                }
            }
        }

        return false;
    }
}
