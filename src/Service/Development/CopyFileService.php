<?php

declare(strict_types=1);

namespace App\Service\Development;

use App\Entity\Core\Website;
use Monolog\Level;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * CopyFileService.
 *
 * To copy file from path to other path
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CopyFileService
{
    private string $baseDirname;
    private Filesystem $filesystem;

    /**
     * CopyFileService constructor.
     */
    public function __construct(
        private readonly string $projectDir,
        private readonly LogService $logService,
    ) {
        $this->baseDirname = $this->projectDir.'/public/uploads/';
        $this->filesystem = new Filesystem();
    }

    /**
     * Copy File.
     */
    public function copy(Website $website, string $path, string $filename, ?string $dirname = null): object
    {
        try {
            $fileDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.$path);
            $uploadDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->baseDirname.$website->getUploadDirname().'/'.$dirname);
            $fileExist = $this->filesystem->exists($fileDirname);
            $inUploadedFileExist = $this->filesystem->exists($uploadDirname.$filename);
            $file = $fileExist ? new File($fileDirname) : null;
            $filename = !$inUploadedFileExist ? $filename : $this->filename($uploadDirname, $filename, $file);

            if ($fileExist && !is_dir($path)) {
                $tmpDirname = $this->baseDirname.'tmp/'.$file->getFilename();
                $this->filesystem->copy($file->getPathname(), $tmpDirname);

                $tmpFile = new File($tmpDirname);
                $uploadedFile = new UploadedFile($tmpFile->getPathname(), $tmpFile->getFilename(), $tmpFile->getMimeType(), null, true);
                $uploadedFile->move($uploadDirname, $filename);

                $this->logService->log('OK', Level::Info, 'copy-file', 'Dirname: '.$path);

                return (object) [
                    'success' => true,
                    'filename' => $filename,
                    'extension' => $file->getExtension(),
                ];
            } elseif (is_dir($path)) {
                $this->logService->log('IS_DIR', Level::Warning, 'copy-file', 'Dirname: '.$path);
            } else {
                $this->logService->log('NO_EXISTING_FILE', Level::Warning, 'copy-file', 'Dirname: '.$path);
            }
        } catch (\Exception $exception) {
            $this->logService->log('EXCEPTION', Level::Critical, 'copy-file', 'Error: '.$exception->getMessage().' for dirname: '.$path);

            return (object) [
                'success' => false,
            ];
        }

        return (object) [
            'success' => false,
        ];
    }

    /**
     * To set filename.
     */
    private function filename(string $uploadDirname, string $filename, ?File $file = null): string
    {
        if ($file instanceof File) {
            $filename = str_replace('.'.$file->getExtension(), '', $filename).'-'.uniqid().'.'.$file->getExtension();
            $inUploadedFileExist = $this->filesystem->exists($uploadDirname.$filename);
            if ($inUploadedFileExist) {
                return $this->filename($uploadDirname, $filename);
            }
        }

        return $filename;
    }
}
