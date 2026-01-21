<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\CalendarException;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CalendarExceptionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CalendarExceptionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CalendarExceptionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('startDate', Type\DateTimeType::class, [
            'required' => true,
            'label' => $this->translator->trans('Date de début', [], 'admin'),
            'placeholder' => [
                'year' => $this->translator->trans('Année', [], 'admin'),
                'month' => $this->translator->trans('Mois', [], 'admin'),
                'day' => $this->translator->trans('Jour', [], 'admin'),
                'hour' => $this->translator->trans('Heure', [], 'admin'),
                'minute' => $this->translator->trans('Minute', [], 'admin'),
                'second' => $this->translator->trans('Seconde', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('endDate', Type\DateTimeType::class, [
            'required' => true,
            'placeholder' => [
                'year' => $this->translator->trans('Année', [], 'admin'),
                'month' => $this->translator->trans('Mois', [], 'admin'),
                'day' => $this->translator->trans('Jour', [], 'admin'),
                'hour' => $this->translator->trans('Heure', [], 'admin'),
                'minute' => $this->translator->trans('Minute', [], 'admin'),
                'second' => $this->translator->trans('Seconde', [], 'admin'),
            ],
            'label' => $this->translator->trans('Date de fin', [], 'admin'),
            'constraints' => [new Assert\NotBlank()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarException::class,
            'website' => null,
            'legend' => $this->translator->trans('Exceptions', [], 'admin'),
            'translation_domain' => 'admin',
        ]);
    }
}
