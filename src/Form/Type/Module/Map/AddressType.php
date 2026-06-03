<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Map;

use App\Entity\Module\Map\Address;
use App\Form\Validator\ZipCode;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AddressType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AddressType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AddressType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isRequired = in_array('latitude', $options['required_fields']);
        $builder->add('latitude', Type\TextType::class, [
            'label' => $this->translator->trans('Latitude', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 latitude',
                'placeholder' => $this->translator->trans('Saisissez une latitude', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('longitude', $options['required_fields']);
        $builder->add('longitude', Type\TextType::class, [
            'label' => $this->translator->trans('Longitude', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 longitude',
                'placeholder' => $this->translator->trans('Saisissez une longitude', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('name', $options['required_fields']);
        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans('Raison sociale', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une raison sociale', [],
                'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('address', $options['required_fields']);
        $builder->add('address', Type\TextType::class, [
            'label' => $this->translator->trans('Adresse', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 address',
                'placeholder' => $this->translator->trans('Saisissez une adresse', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-9'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('city', $options['required_fields']);
        $builder->add('city', Type\TextType::class, [
            'label' => $this->translator->trans('Ville', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 city',
                'placeholder' => $this->translator->trans('Saisissez une ville', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('zipCode', $options['required_fields']);
        $builder->add('zipCode', Type\TextType::class, [
            'label' => $this->translator->trans('Code postal', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 zip-code',
                'placeholder' => $this->translator->trans('Saisissez un code postal', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new ZipCode(), new NotBlank()] : [],
        ]);

        $isRequired = in_array('department', $options['required_fields']);
        $builder->add('department', Type\TextType::class, [
            'label' => $this->translator->trans('Département', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 department',
                'placeholder' => $this->translator->trans('Saisissez un département', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('region', $options['required_fields']);
        $builder->add('region', Type\TextType::class, [
            'label' => $this->translator->trans('Région', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['class' => 'col-12 region',
                'placeholder' => $this->translator->trans('Saisissez une région', [],
                    'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('country', $options['required_fields']);
        $builder->add('country', Type\CountryType::class, [
            'label' => $this->translator->trans('Pays', [], 'admin'),
            'required' => $isRequired,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez un pays', [], 'admin'),
            'attr' => ['class' => 'col-12 country'],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('googleMapUrl', $options['required_fields']);
        $builder->add('googleMapUrl', Type\UrlType::class, [
            'label' => $this->translator->trans('Google map URL', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une URL', [],
                'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);

        $isRequired = in_array('googleMapDirectionUrl', $options['required_fields']);
        $builder->add('googleMapDirectionUrl', Type\UrlType::class, [
            'label' => $this->translator->trans('Google map itinéraire URL', [], 'admin'),
            'required' => $isRequired,
            'attr' => ['placeholder' => $this->translator->trans('Saisissez une URL', [],
                'admin')
            ],
            'row_attr' => ['class' => 'col-12 col-md-12 col-lg-6'],
            'constraints' => $isRequired ? [new NotBlank()] : [],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'website' => null,
            'required_fields' => ['latitude', 'longitude'],
            'translation_domain' => 'admin',
        ]);
    }
}
