<?php

declare(strict_types=1);

namespace App\Entity\Core;

use App\Entity\BaseIntl;
use App\Repository\Core\ModuleIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ModuleIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'core_module_intls')]
#[ORM\Entity(repositoryClass: ModuleIntlRepository::class)]
class ModuleIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Module::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Module $module = null;

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): static
    {
        $this->module = $module;

        return $this;
    }
}
