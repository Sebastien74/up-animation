<?php

declare(strict_types=1);

namespace App\Naming;

use App\Entity\Media\Media;
use App\Service\Interface\CoreLocatorInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * WebsiteDirectoryNamer.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class WebsiteDirectoryNamer implements DirectoryNamerInterface
{
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @param Media $object
     */
    public function directoryName($object, PropertyMapping $mapping): string
    {
        $website = method_exists($object, 'getWebsite')
            ? $object->getWebsite()
            : $this->coreLocator->website()->entity;

        if ($website && $website->getUploadDirname()) {
            return $website->getUploadDirname();
        }

        return '';
    }
}
