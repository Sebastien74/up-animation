<?php

declare(strict_types=1);

namespace App\Entity\Layout;

use App\Entity\BaseIntl;
use App\Repository\Layout\FieldValueIntlRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * FieldValueIntl.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'layout_field_value_intls')]
#[ORM\Entity(repositoryClass: FieldValueIntlRepository::class)]
class FieldValueIntl extends BaseIntl
{
    #[ORM\ManyToOne(targetEntity: FieldValue::class, cascade: ['persist'], inversedBy: 'intls')]
    #[ORM\JoinColumn(onDelete: 'cascade')]
    private ?FieldValue $fieldValue = null;

    public function getFieldValue(): ?FieldValue
    {
        return $this->fieldValue;
    }

    public function setFieldValue(?FieldValue $fieldValue): static
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }
}
