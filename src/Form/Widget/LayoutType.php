<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Layout\Layout;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LayoutType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * LayoutType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('associatedEntitiesDisplay', ChoiceType::class, [
            'label' => $this->translator->trans('Affichage des entités associées', [], 'admin'),
            'choices' => [
                $this->translator->trans('Carrousel', [], 'admin') => 'slider',
                $this->translator->trans('Mini-fiches', [], 'admin') => 'cards',
            ],
            'row_attr' => ['class' => 'col-lg-4'],
            'display' => 'search',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Layout::class,
        ]);
    }
}