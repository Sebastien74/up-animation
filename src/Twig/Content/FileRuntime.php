<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Command\AssetsCommand;
use App\Command\JsRoutingCommand;
use App\Service\Core\FileInfo;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * FileRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FileRuntime implements RuntimeExtensionInterface
{
    /**
     * FileRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FileInfo $fileInfo,
        private readonly AssetsCommand $assetsCommand,
        private readonly JsRoutingCommand $jsRoutingCommand,
    ) {
    }

    /**
     * Check if file exist.
     */
    public function fileInfo(mixed $website, ?string $filename = null, ?string $dirname = null): ?FileInfo
    {
        return $filename ? $this->fileInfo->file($website, $filename, $dirname) : null;
    }

    /**
     * Get file Bytes.
     */
    public function formatBytes($bytes, int $precision = 2): string
    {
        return $this->fileInfo->getFormatBytes($bytes, $precision);
    }

    /**
     * To convert to Kilobytes.
     */
    public function convertToKilobytes($value): float|int
    {
        $units = ['K' => 1, 'M' => 1024, 'G' => 1048576, 'T' => 1073741824];
        $unit = strtoupper(substr($value, -1));
        $num = (int) $value;
        if (isset($units[$unit])) {
            return $num * $units[$unit];
        }

        return $num / 1024;
    }

    /**
     * To convert to Bytes.
     */
    public function convertToBytesSize($value): int
    {
        return (int) preg_replace_callback('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', function ($m) {
            switch (strtolower($m[2])) {
                case 't': $m[1] *= 1024;
                    // no break
                case 'g': $m[1] *= 1024;
                    // no break
                case 'm': $m[1] *= 1024;
                    // no break
                case 'k': $m[1] *= 1024;
            }

            return $m[1];
        }, $value);
    }

    /**
     * Check if file exist.
     */
    public function fileExist(?string $path = null, string $dir = '/templates/'): bool
    {
        return $this->coreLocator->fileExist($path, $dir);
    }

    /**
     * To set jsRouting file.
     */
    public function jsRouting(?string $filename = null, bool $all = false): void
    {
        $filesystem = new Filesystem();
        $masterDirname = $this->coreLocator->projectDir().'/public/js/fos_js_routes.json';
        $masterDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $masterDirname);
        $routerDirname = $this->coreLocator->projectDir().'/public/bundles/fosjsrouting/js/router.min.js';
        $routerDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $routerDirname);
        $fileDirname = $this->coreLocator->projectDir().'/public/js/'.$filename.'.json';
        $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileDirname);

        if (!$filesystem->exists($masterDirname)
            || !$filesystem->exists($routerDirname)
            || $filename && !$filesystem->exists($fileDirname)) {
            $this->assetsCommand->install();
            $this->jsRoutingCommand->dump($filename, $all);
        }
    }
}
