<?php

declare(strict_types=1);

namespace App\Naming;

use App\Service\Core\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\NamerInterface;

/**
 * OrUniqueNamer.
 *
 * Use the original filename if not exists, otherwise add unique id.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OrUniqueNamer implements NamerInterface
{
    public function name($object, PropertyMapping $mapping): string
    {
        /** @var UploadedFile $file */
        $file = $mapping->getFile($object);
        $originalName = $file->getClientOriginalName();
        $extension = $file->guessExtension();
        $basename = pathinfo($originalName, PATHINFO_FILENAME);

        $urlizedName = Urlizer::urlize($basename);
        $filename = $urlizedName . '.' . $extension;

        $uploadDir = $mapping->getUploadDestination();
        
        // If directory namer is used, we need to take it into account
        $directory = $mapping->getUploadDir($object);
        if ($directory) {
            $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $directory;
        } else {
            $uploadPath = $uploadDir;
        }

        if (!file_exists($uploadPath . DIRECTORY_SEPARATOR . $filename)) {
            return $filename;
        }

        return $urlizedName . '-' . uniqid() . '.' . $extension;
    }
}
