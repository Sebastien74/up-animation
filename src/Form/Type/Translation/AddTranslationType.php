<?php

declare(strict_types=1);

namespace App\Form\Type\Translation;

use App\Entity\Translation\TranslationDomain;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AddTranslationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AddTranslationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AddTranslationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('domain', EntityType::class, [
            'display' => 'search',
            'label' => $this->translator->trans('Domaine de traduction', [], 'admin'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => 'col-md-4',
            ],
            'class' => TranslationDomain::class,
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('keyName', Type\TextType::class, [
            'label' => $this->translator->trans('Clé de traduction', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez la clé', [], 'admin'),
                'group' => 'col-md-8',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, [
            'only_save' => true,
            'as_ajax' => true,
            'refresh' => true,
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
