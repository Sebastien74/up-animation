<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FormatDateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormatDateType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FormatDateType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Format de date', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'empty_data' => 'dd/MM/Y',
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => [
                'group' => 'col-md-3',
                'data-config' => true,
            ],
            'choices' => [
                $this->translator->trans('jj/mm', [], 'admin') => 'dd/MM',
                $this->translator->trans('jj/mm/aaaa', [], 'admin') => 'dd/MM/Y',
                $this->translator->trans('jour jj/mm/aaaa', [], 'admin') => 'cccc dd/MM/Y',
                $this->translator->trans('jour jj mois aaaa', [], 'admin') => 'cccc dd MMMM Y',
                $this->translator->trans('jj mois aaaa', [], 'admin') => 'dd MMMM Y',
                $this->translator->trans('jj m. aaaa', [], 'admin') => 'dd MMM Y',
                $this->translator->trans('jj/mm/aaaa à hh:mm', [], 'admin') => 'dd/MM/Y à HH:mm',
                $this->translator->trans('jour jj/mm/aaaa à hh:mm', [], 'admin') => 'cccc dd/MM/Y à HH:mm',
                $this->translator->trans('jour jj mois aaaa à hh:mm', [], 'admin') => 'cccc dd MMMM Y à HH:mm',
                $this->translator->trans('jj mois aaaa à hh:mm', [], 'admin') => 'dd MMMM Y à HH:mm',
                $this->translator->trans('j3. dd m3.', [], 'admin') => 'EEE dd MMM',
                $this->translator->trans('dd m3.', [], 'admin') => 'dd MMM',
            ],
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
