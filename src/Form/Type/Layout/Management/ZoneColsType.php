<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Layout\Col;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ZoneColsType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ZoneColsType extends AbstractType
{
    /**
     * ZoneColsType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $margins = new ScreensType($this->coreLocator);
        $margins->add($builder, [
            'entity' => $options['zone'],
            'mobilePositionLabel' => $options['mobilePositionLabel'],
            'tabletPositionLabel' => $options['tabletPositionLabel'],
            'mobileSizeLabel' => $options['mobileSizeLabel'],
            'tabletSizeLabel' => $options['tabletSizeLabel'],
        ]);

        $builder->add('alignment', AlignmentType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Col::class,
            'mobilePositionLabel' => true,
            'tabletPositionLabel' => true,
            'mobilePositionGroup' => 'col-md-6',
            'tabletPositionGroup' => 'col-md-6',
            'mobileSizeLabel' => true,
            'tabletSizeLabel' => true,
            'mobileSizeGroup' => 'col-md-6',
            'tabletSizeGroup' => 'col-md-6',
            'website' => null,
            'zone' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
