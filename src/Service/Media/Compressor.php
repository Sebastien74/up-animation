<?php

declare(strict_types=1);

namespace App\Service\Media;

use Symfony\Component\Process\Process;

/**
 * Compressor.
 *
 * Optimize images using external tools (jpegoptim, pngquant, etc.) or Imagick.
 */
class Compressor
{
    private array $binCache = [];

    /**
     * Optimize image.
     */
    public function optimize(string $path, string $mime, int $quality = 85): void
    {
        if (!file_exists($path) || filesize($path) <= 500 * 1024) {
            return;
        }

        $optimized = false;

        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $optimized = $this->tryJpegTools($path, $quality);
        } elseif ($mime === 'image/png') {
            $optimized = $this->tryPngTools($path);
        } elseif ($mime === 'image/webp') {
            $optimized = $this->tryImagick($path, $quality);
        }

        if (!$optimized && extension_loaded('imagick')) {
            $this->tryImagick($path, $quality);
        }
    }

    private function tryJpegTools(string $path, int $quality): bool
    {
        // Try jpegoptim
        if ($this->which('jpegoptim')) {
            $this->run(sprintf('jpegoptim --max=%d --strip-all --all-progressive %s', $quality, escapeshellarg($path)));
            return true;
        }

        // Try mozjpeg (cjpeg)
        if ($this->which('cjpeg')) {
            $tmpPath = $path . '.tmp';
            $this->run(sprintf('cjpeg -quality %d -progressive -outfile %s %s', $quality, escapeshellarg($tmpPath), escapeshellarg($path)));
            if (file_exists($tmpPath) && filesize($tmpPath) < filesize($path)) {
                rename($tmpPath, $path);
                return true;
            }
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        return false;
    }

    private function tryPngTools(string $path): bool
    {
        // Try pngquant
        if ($this->which('pngquant')) {
            $tmpPath = $path . '-tmp.png';
            $this->run(sprintf('pngquant --quality=65-80 --speed 1 --force --output %s %s', escapeshellarg($tmpPath), escapeshellarg($path)));
            if (file_exists($tmpPath) && filesize($tmpPath) < filesize($path)) {
                rename($tmpPath, $path);
                return true;
            }
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        // Try optipng
        if ($this->which('optipng')) {
            $this->run(sprintf('optipng -o2 -strip all %s', escapeshellarg($path)));
            return true;
        }

        return false;
    }

    private function tryImagick(string $path, int $quality): bool
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        try {
            $imagick = new \Imagick($path);
            $imagick->stripImage();
            $imagick->setImageCompressionQuality($quality);
            $imagick->writeImage($path);
            $imagick->clear();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function run(string $command): void
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(10);
        try {
            $process->run();
        } catch (\Exception $e) {
            // Ignore
        }
    }

    private function which(string $bin): ?string
    {
        if (isset($this->binCache[$bin])) {
            return $this->binCache[$bin];
        }

        $command = DIRECTORY_SEPARATOR === '\\' ? 'where ' . $bin : 'command -v ' . $bin;
        $process = Process::fromShellCommandline($command);
        try {
            $process->run();
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                $lines = explode("\n", $output);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line && !str_contains($line, 'INFO:')) {
                        return $this->binCache[$bin] = $line;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return $this->binCache[$bin] = null;
    }
}
