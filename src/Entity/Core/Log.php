<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseEntity;
use App\Repository\Core\LogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Log.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_log')]
#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Log extends BaseEntity
{
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $asRead = false;

    public function isAsRead(): ?bool
    {
        return $this->asRead;
    }

    public function setAsRead(bool $asRead): static
    {
        $this->asRead = $asRead;

        return $this;
    }
}
