<?php

declare(strict_types=1);

namespace App\Service\Core;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * FileInfo.
 *
 * To get file info.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FileInfo::class, 'key' => 'file_info_service'],
])]
class FileInfo
{
    private ?Request $request = null;
    private ?Filesystem $filesystem = null;
    private ?string $websiteDirname = null;
    private ?string $uploadsPath;
    private ?string $projectDir;
    private ?RequestStack $requestStack;
    private ?string $dirname = null;
    private ?string $extension = null;
    private ?string $filename = null;
    private ?string $path = null;
    private ?string $attributes = null;
    private ?string $mime = null;
    private ?int $size = null;
    private ?int $width = null;
    private ?int $height = null;
    private ?int $bits = null;
    private ?string $formatBytes = null;
    private bool $isImage = false;
    private bool $isPlaceHolder = false;
    private array $preserved = ['preserved', 'projectDir', 'uploadsPath', 'requestStack', 'cache', 'coreLocator'];
    private array $cache = [];

    public function __construct(private readonly CoreLocatorInterface $coreLocator) {
        $this->uploadsPath = $this->coreLocator->uploadDir();
        $this->projectDir = $this->coreLocator->projectDir();
        $this->requestStack = $this->coreLocator->requestStack();
    }

    /**
     * Get file info.
     */
    public function file(mixed $website, ?string $filename, ?string $dirname = null): static
    {
        $cacheKey = ($filename ?: '').($dirname ?: '');

        if (isset($this->cache[$cacheKey])) {
            foreach (get_object_vars($this->cache[$cacheKey]) as $key => $value) {
                if (!in_array($key, $this->preserved)) {
                    $this->$key = $value;
                }
            }
            return $this;
        }

        $this->resetValues();

        if (!$this->request instanceof Request) {
            $this->request = $this->requestStack->getMainRequest();
        }

        if (!$this->filesystem instanceof Filesystem) {
            $this->filesystem = new Filesystem();
        }

        $this->websiteDirname = $website instanceof Website ? $website->getUploadDirname() : ($website instanceof WebsiteModel ? $website->uploadDirname : null);
        $this->setDirname($dirname, $filename);
        $this->setPlaceholder();
        $this->splFileInfo();

        $preserved = $this->preserved;
        $data = array_filter(get_object_vars($this), function ($key) use ($preserved) {
            return !in_array($key, $preserved);
        }, ARRAY_FILTER_USE_KEY);
        $this->cache[$cacheKey] = (object) $data;

        return $this;
    }

    /**
     * To set dirname.
     */
    private function setDirname(?string $dirname, ?string $filename): void
    {
        $dirname = $dirname ? str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname) : null;
        $dirname = $dirname ? str_replace($this->projectDir.DIRECTORY_SEPARATOR.'public', '', $dirname) : $dirname;
        if (($dirname && str_contains($dirname, 'thumbnails'.DIRECTORY_SEPARATOR))
            || ($dirname && str_contains($dirname, DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR))) {
            $host = $this->request->getSchemeAndHttpHost();
            $dirname = ltrim($dirname, DIRECTORY_SEPARATOR);
            $dirname = $this->projectDir.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.str_replace($host, '', $dirname);
        } elseif ($dirname && str_contains($dirname, DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR)) {
            $dirname = $this->uploadsPath.str_replace(DIRECTORY_SEPARATOR.'uploads', '', $dirname);
        } elseif (!$dirname) {
            $dirname = $this->uploadsPath.DIRECTORY_SEPARATOR.$this->websiteDirname.DIRECTORY_SEPARATOR.$filename;
        }
        $this->dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
    }

    /**
     * To set placeholder.
     */
    private function setPlaceholder(): void
    {
        if ($this->dirname && !$this->filename && str_contains($this->dirname, '.')) {
            $matches = explode(DIRECTORY_SEPARATOR, $this->dirname);
            $this->filename = end($matches);
        }

        $this->isPlaceHolder = $this->dirname && str_contains($this->dirname, 'vendor'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'placeholder');
        if ($this->dirname && $this->filename && !$this->filesystem->exists($this->dirname)) {
            $this->isPlaceHolder = true;
            $this->dirname = $this->projectDir.DIRECTORY_SEPARATOR.'public/medias/placeholder-dark.jpg';
            $this->dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->dirname);
        }
    }

    /**
     * To get SplFileInfo.
     */
    private function splFileInfo(): void
    {
        if (!is_dir($this->dirname) && $this->filesystem->exists($this->dirname)) {
            $sizes = getimagesize($this->dirname);
            $file = new File($this->dirname);
            $infos = new SplFileInfo($this->dirname, $file->getPathname(), $file->getFilename());
            $this->extension = $infos->getExtension();
            $this->filename = $infos->getFilename();
            $this->path = str_replace([$this->projectDir.DIRECTORY_SEPARATOR.'public', DIRECTORY_SEPARATOR], ['', '/'], $this->dirname);
            $this->attributes = !empty($sizes[3]) ? $sizes[3] : null;
            $this->mime = !empty($sizes['mime']) ? $sizes['mime'] : null;
            $this->size = $infos->getSize();
            $this->width = !empty($sizes[0]) ? $sizes[0] : null;
            $this->height = !empty($sizes[1]) ? $sizes[1] : null;
            $this->bits = !empty($sizes['bits']) ? $sizes['bits'] : null;
            $this->isImage = @is_array($sizes);
            $this->formatBytes($this->size);
        }
    }

    /**
     * Get file Bytes.
     */
    private function formatBytes($bytes = null, int $precision = 2): void
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $this->formatBytes = number_format($bytes / pow(1024, $power), $precision, '.', ',').' '.$units[$power];
    }

    /**
     * To reset values.
     */
    private function resetValues(): void
    {
        $asBool = ['isImage', 'isPlaceHolder'];
        $reflectionObject = new \ReflectionObject($this);
        $properties = $reflectionObject->getProperties();
        foreach ($properties as $property) {
            if (!in_array($property->getName(), $this->preserved) && !$property->isReadOnly()) {
                $method = $property->getName();
                $this->$method = in_array($property->getName(), $asBool) ? false : null;
            }
        }
    }

    /**
     * To get dirname.
     */
    public function getDirname(): ?string
    {
        return $this->dirname;
    }

    /**
     * To get extension.
     */
    public function getExtension(): ?string
    {
        return $this->extension;
    }

    /**
     * To get filename.
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * To get path.
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * To get attributes.
     */
    public function getAttributes(): ?string
    {
        return $this->attributes;
    }

    /**
     * To get mime.
     */
    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * To get size.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * To get width.
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * To get height.
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * To get bits.
     */
    public function getBits(): ?int
    {
        return $this->bits;
    }

    /**
     * To get isImage.
     */
    public function isImage(): bool
    {
        return $this->isImage;
    }

    /**
     * To get isPlaceHolder.
     */
    public function isPlaceHolder(): bool
    {
        return $this->isPlaceHolder;
    }

    /**
     * To get formatBytes.
     */
    public function getFormatBytes($bytes = null, int $precision = 2): ?string
    {
        if ($bytes) {
            $this->resetValues();
            $this->formatBytes($bytes, $precision);
        }

        return $this->formatBytes;
    }
}
