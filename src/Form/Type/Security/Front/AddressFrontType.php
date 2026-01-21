<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Entity\Information\Address;
use App\Form\Validator;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AddressFrontType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AddressFrontType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AddressFrontType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!in_array('address', $options['excluded_fields'])) {
            $constraints = $this->getConstraints('address', $options);
            $builder->add('address', Type\TextType::class, [
                'label' => $this->translator->trans('Adresse', [], 'admin'),
                'required' => $constraints['required'],
                'attr' => [
                    'group' => !empty($options['groups_classes']['address']) ? $options['groups_classes']['address'] : 'col-12',
                    'placeholder' => $this->translator->trans('Saisissez une adresse', [], 'admin'),
                ],
                'constraints' => $constraints['validators'],
            ]);
        }

        if (!in_array('zipCode', $options['excluded_fields'])) {
            $departmentsToString = '';
            foreach ($options['departments'] as $department) {
                $number = $department < 10 ? '0'.$department : $department;
                $departmentsToString .= ','.$number.' ';
            }
            $options['constraints_fields']['zipCode'] = [new Validator\ZipCode(['departments' => $options['departments']])];
            $constraints = $this->getConstraints('zipCode', $options);
            $help = !empty($options['departments']) ? $this->translator->trans('Inscription possible uniquement pour les résidents des %count% départements suivants : <br /> '.ltrim($departmentsToString, ','), ['%count%' => count($options['departments'])], 'admin') : null;
            $builder->add('zipCode', Type\TextType::class, [
                'label' => $this->translator->trans('Code postal', [], 'admin'),
                'required' => $constraints['required'],
                'attr' => [
                    'group' => !empty($options['groups_classes']['zipCode']) ? $options['groups_classes']['zipCode'] : 'col-lg-4',
                    'placeholder' => $this->translator->trans('Saisissez un code postal', [], 'admin'),
                ],
                'constraints' => $constraints['validators'],
                'help' => $help,
            ]);
        }

        if (!in_array('city', $options['excluded_fields'])) {
            $constraints = $this->getConstraints('city', $options);
            $builder->add('city', Type\TextType::class, [
                'label' => $this->translator->trans('Ville', [], 'admin'),
                'required' => $constraints['required'],
                'attr' => [
                    'group' => !empty($options['groups_classes']['city']) ? $options['groups_classes']['city'] : 'col-lg-8',
                    'placeholder' => $this->translator->trans('Saisissez une ville', [], 'admin'),
                ],
                'constraints' => $constraints['validators'],
            ]);
        }

        if (!in_array('department', $options['excluded_fields'])) {
            $constraints = $this->getConstraints('department', $options);
            $builder->add('department', Type\TextType::class, [
                'label' => $this->translator->trans('Département', [], 'admin'),
                'required' => $constraints['required'],
                'attr' => [
                    'group' => !empty($options['groups_classes']['department']) ? $options['groups_classes']['department'] : 'col-lg-4',
                    'placeholder' => $this->translator->trans('Saisissez une département', [], 'admin'),
                ],
                'constraints' => $constraints['validators'],
            ]);
        }

        if (!in_array('region', $options['excluded_fields'])) {
            $constraints = $this->getConstraints('region', $options);
            $builder->add('region', Type\TextType::class, [
                'label' => $this->translator->trans('Région', [], 'admin'),
                'required' => $constraints['required'],
                'attr' => [
                    'group' => !empty($options['groups_classes']['region']) ? $options['groups_classes']['region'] : 'col-lg-4',
                    'placeholder' => $this->translator->trans('Saisissez une région', [], 'admin'),
                ],
                'constraints' => $constraints['validators'],
            ]);
        }

        if (!in_array('country', $options['excluded_fields'])) {
            $constraints = $this->getConstraints('country', $options);
            $builder->add('country', Type\CountryType::class, [
                'label' => $this->translator->trans('Pays', [], 'admin'),
                'required' => $constraints['required'],
                'display' => 'search',
                'placeholder' => $this->translator->trans('Sélectionnez un pays', [], 'admin'),
                'attr' => [
                    'group' => !empty($options['groups_classes']['country']) ? $options['groups_classes']['country'] : 'col-lg-4',
                ],
                'constraints' => $constraints['validators'],
            ]);
        }
    }

    /**
     * Get constraints.
     */
    private function getConstraints(string $field, array $options = []): array
    {
        $isRequired = is_array($options['required_fields']) && in_array($field, $options['required_fields']) || '*' === $options['required_fields'];

        $constraints['required'] = $isRequired;
        $constraints['validators'] = [];

        if ($isRequired) {
            $constraints['validators'][] = new NotBlank();
        }

        if (!empty($options['constraints_fields'][$field]) && is_array($options['constraints_fields'][$field])) {
            $constraints['validators'] = array_merge($constraints['validators'], $options['constraints_fields'][$field]);
        }

        return $constraints;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'website' => null,
            'departments' => [],
            'groups_classes' => [],
            'constraints_fields' => [],
            'required_fields' => '*',
            'excluded_fields' => [],
            'translation_domain' => 'front',
        ]);
    }
}
