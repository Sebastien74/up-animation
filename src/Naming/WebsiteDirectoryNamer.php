<?php

declare(strict_types=1);

namespace App\Naming;

use App\Entity\Media\Media;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * WebsiteDirectoryNamer.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * @param Media $object
     */
    public function directoryName($object, PropertyMapping $mapping): string
    {
        $website = $object->getWebsite();

        if ($website && $website->getUploadDirname()) {
            return $website->getUploadDirname();
        }

        return '';
    }
}
