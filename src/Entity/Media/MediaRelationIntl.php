<?php

declare(strict_types=1);

namespace App\Entity\Media;

use App\Entity\BaseIntl;
use App\Repository\Media\MediaRelationIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * MediaRelationIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'media_relation_intl')]
#[ORM\Entity(repositoryClass: MediaRelationIntlRepository::class)]
class MediaRelationIntl extends BaseIntl
{

}
