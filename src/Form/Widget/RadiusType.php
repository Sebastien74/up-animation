<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RadiusType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RadiusType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * RadiusType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Generate AdminName Type.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add('radius', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Arrondir les angles', [], 'admin'),
            'attr' => ['group' => !empty($options['group']) ? $options['group'] : 'col-md-3', 'class' => 'w-100'],
        ]);
    }
}
