<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Module;
use App\Entity\Layout\BlockType;
use App\Entity\Layout\LayoutConfiguration;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LayoutConfigurationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LayoutConfigurationType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    /**
     * LayoutConfigurationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => 'col-12']);

        if (!$isNew) {

            $builder->add('entity', ChoiceType::class, [
                'label' => $this->translator->trans('Entité', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
                'constraints' => [new NotBlank()],
                'display' => 'search',
                'choices' => $this->getEntities(),
                'choice_translation_domain' => false,
            ]);

            $builder->add('blockMarginBottom', ChoiceType::class, [
                'label' => $this->translator->trans('Marge inférieure par défaut des blocs', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
                'constraints' => [new NotBlank()],
                'display' => 'search',
                'choices' => $this->getMargins(),
                'choice_translation_domain' => false,
            ]);

            $builder->add('titleMarginBottom', ChoiceType::class, [
                'label' => $this->translator->trans('Marge inférieure par défaut des titres', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-4'],
                'constraints' => [new NotBlank()],
                'display' => 'search',
                'choices' => $this->getMargins(),
                'choice_translation_domain' => false,
            ]);

            $builder->add('modules', EntityType::class, [
                'label' => $this->translator->trans('Modules', [], 'admin'),
                'class' => Module::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.adminName', 'ASC');
                },
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
                'required' => false,
                'display' => 'search',
            ]);

            $builder->add('blockTypes', EntityType::class, [
                'label' => $this->translator->trans('Types de blocs', [], 'admin'),
                'class' => BlockType::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('b')
                        ->orderBy('b.adminName', 'ASC');
                },
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
                'required' => false,
                'display' => 'select-2',
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get Entities.
     */
    private function getEntities(): array
    {
        $entities = [];
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $data) {
            if (0 === $data->getReflectionClass()->getModifiers()) {
                $namespace = $data->getName();
                $entity = new $namespace();
                if (method_exists($entity, 'getLayout')) {
                    $entities[$this->translator->trans($namespace, [], 'entity')] = $namespace;
                }
            }
        }

        return $entities;
    }

    /**
     * Get margins.
     */
    private function getMargins(): array
    {
        return [
            '0' => 'mb-0',
            'XS' => 'mb-xs',
            'S' => 'mb-sm',
            'M' => 'mb-md',
            'L' => 'mb-lg',
            'XL' => 'mb-xl',
            'XXL' => 'mb-xxl',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LayoutConfiguration::class,
            'website' => null,
            'isNew' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
