<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType as SymfonySubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SubmitDuplicateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubmitDuplicateType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SubmitDuplicateType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * Generate submit Type.
     */
    public function add(FormBuilderInterface $builder, array $options = []): void
    {
        $builder->add('save', SymfonySubmitType::class, [
            'label' => $this->translator->trans('Dupliquer', [], 'admin'),
            'attr' => [
                'class' => 'btn btn-outline-white',
            ],
        ]);
    }
}
