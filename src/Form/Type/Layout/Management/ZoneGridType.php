<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\Grid;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ZoneGridType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneGridType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ZoneGridType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('grid', EntityType::class, [
            'label' => false,
            'class' => Grid::class,
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'expanded' => true,
            'row_attr' => ['class' => 'disabled-floating'],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Ajouter', [], 'admin'),
            'attr' => [
                'class' => 'btn-info d-none edit-element-submit-btn btn-lg disable-preloader',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
