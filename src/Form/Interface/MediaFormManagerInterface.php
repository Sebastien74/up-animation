<?php

declare(strict_types=1);

namespace App\Form\Interface;

use App\Form\Manager\Media;

/**
 * MediaFormManagerInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface MediaFormManagerInterface
{
    public function library(): Media\MediaLibraryManager;
    public function media(): Media\MediaManager;
    public function modalLibrary(): Media\ModalLibraryManager;
    public function search(): Media\SearchManager;
}