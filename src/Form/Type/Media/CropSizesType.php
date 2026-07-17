<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Entity\Media\CropSizes;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CropSizesType.
 *
 * Sous-formulaire de l'objet embarqué CropSizes : largeur + hauteur de vignette
 * par écran. Chaque champ porte data-screen-tab (desktop/laptop/tablet/mobile)
 * pour permettre un rendu en onglets par écran côté back.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CropSizesType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $placeholder = $this->translator->trans('Saisissez un chiffre (px)', [], 'admin');
        $screens = [
            'Desktop' => ['tab' => 'desktop', 'label' => $this->translator->trans('Ordinateur', [], 'admin')],
            'Laptop' => ['tab' => 'laptop', 'label' => $this->translator->trans('Ordinateur portable', [], 'admin')],
            'Tablet' => ['tab' => 'tablet', 'label' => $this->translator->trans('Tablette', [], 'admin')],
            'Mobile' => ['tab' => 'mobile', 'label' => $this->translator->trans('Mobile', [], 'admin')],
        ];

        foreach ($screens as $screen => $config) {
            $builder->add('cropWidth'.$screen, Type\IntegerType::class, [
                'label' => $this->translator->trans('Largeur (%screen%)', ['%screen%' => $config['label']], 'admin'),
                'required' => false,
                'attr' => ['placeholder' => $placeholder, 'data-config' => true, 'min' => 0],
                'row_attr' => ['class' => 'col-6 crop-size-field', 'data-screen-tab' => $config['tab']],
            ]);
            $builder->add('cropHeight'.$screen, Type\IntegerType::class, [
                'label' => $this->translator->trans('Hauteur (%screen%)', ['%screen%' => $config['label']], 'admin'),
                'required' => false,
                'attr' => ['placeholder' => $placeholder, 'data-config' => true, 'min' => 0],
                'row_attr' => ['class' => 'col-6 crop-size-field', 'data-screen-tab' => $config['tab']],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CropSizes::class,
            'label' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'crop_sizes';
    }
}
