<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Service\Core\Urlizer;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * FileUrlizerService.
 *
 * To generate file archive with filename urlized
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FileUrlizerService
{
    /**
     * FileUrlizerService constructor.
     */
    public function __construct(private readonly string $projectDir)
    {
    }

    /**
     * Execute urlizer.
     */
    public function execute(array $files): bool|string
    {
        $tmpDirname = $this->projectDir.'/public/uploads/tmp/rename/';
        $tmpDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $tmpDirname);

        foreach ($files as $file) {
            /** @var UploadedFile $file */
            $extension = $file->guessClientExtension();
            $filename = str_replace('.'.$extension, '', $file->getClientOriginalName());

            $file->move(
                $tmpDirname,
                Urlizer::urlize($filename).'.'.$extension
            );
        }

        return $this->zip($tmpDirname);
    }

    /**
     * Generate ZipArchive.
     */
    public function zip(string $dirname, ?string $filename = null): bool|string
    {
        $finder = Finder::create();
        $finder->files()->in($dirname);
        $zip = new \ZipArchive();
        $zipName = $filename ?: 'rename-files.zip';
        $zipName = !preg_match('/.zip/', $zipName) ? $zipName.'.zip' : $zipName;
        $zip->open($zipName, \ZipArchive::CREATE);

        foreach ($finder as $file) {
            $zip->addFromString($file->getFilename(), $file->getContents());
        }

        $zip->close();

        return $finder->count() ? $zipName : false;
    }
}
