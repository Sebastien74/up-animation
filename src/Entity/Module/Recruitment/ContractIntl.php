<?php

declare(strict_types=1);

namespace App\Entity\Module\Recruitment;

use App\Entity\BaseIntl;
use App\Repository\Module\Newscast\CategoryIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ContractIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'module_recruitment_contract_intls')]
#[ORM\Entity(repositoryClass: CategoryIntlRepository::class)]
class ContractIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: Contract::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?Contract $contract = null;

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }
}
