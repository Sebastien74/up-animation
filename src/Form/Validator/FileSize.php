<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraints\File as FileConstraint;

/**
 * FileSize.
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FileSize extends FileConstraint
{
}
