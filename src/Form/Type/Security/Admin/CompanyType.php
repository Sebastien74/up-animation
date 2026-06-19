<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Entity\Security\Company;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CompanyType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CompanyType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CompanyType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans("Nom de l'entreprise", [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin'),
                'row_attr' => ['class' => $isNew ? 'col-12' : 'col-12 col-md-9'],
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        if (!$isNew) {
            $builder->add('locale', WidgetType\LanguageIconType::class, [
                'required' => false,
                'label' => $this->translator->trans('Langue', [], 'admin'),
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
            ]);

            $builder->add('email', Type\EmailType::class, [
                'required' => false,
                'label' => $this->translator->trans('E-mail', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('contactLastName', Type\EmailType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom du contact principal', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un nom', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('contactFirstName', Type\EmailType::class, [
                'required' => false,
                'label' => $this->translator->trans('Prénom du contact principal', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'admin')
                ],
                'row_attr' => ['class' => 'col-12 col-md-12 col-lg-4'],
            ]);

            $builder->add('file', Type\FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => ['accept' => 'image/*', 'class' => 'col-12 dropify'],
            ]);

            $builder->add('address', CompanyAddressType::class, ['label' => false]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
