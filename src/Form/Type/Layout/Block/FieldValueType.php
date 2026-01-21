<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Entity\Layout\FieldValue;
use App\Entity\Module\Form\Form;
use App\Entity\Module\Form\StepForm;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Translation\i18nRuntime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * FieldValueType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FieldValueType extends AbstractType
{
    /**
     * FieldValueType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly i18nRuntime $i18nRuntime,
    ) {
    }

    /**
     * @throws NonUniqueResultException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Form|StepForm $currentForm */
        $currentForm = $options['currentForm'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder);

        $builder->add('intls', CollectionType::class, [
            'label' => false,
            'entry_type' => FieldValueIntlType::class,
            'prototype' => true,
            'allow_add' => true,
            'allow_delete' => true,
        ]);

        $builder->add('value', EntityType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->coreLocator->translator()->trans('Valeur parente', [], 'admin'),
            'placeholder' => $this->coreLocator->translator()->trans('Sélectionnez', [], 'admin'),
            'class' => FieldValue::class,
            'query_builder' => function (EntityRepository $er) {
                $configuration = $this->coreLocator->em()->getRepository(Block::class)->find($this->coreLocator->request()->get('block'))->getFieldConfiguration();

                return $er->createQueryBuilder('v')
                    ->andWhere('v.configuration = :configuration')
                    ->setParameter('configuration', $configuration)
                    ->orderBy('v.adminName', 'ASC');
            },
            'choice_label' => function ($page) {
                return strip_tags($page->getAdminName());
            },
            'row_attr' => ['class' => 'col-md-4 parent-group'],
        ]);

        if ($currentForm->getConfiguration() && $currentForm->getConfiguration()->isDynamic()) {
            $field = new FieldLayoutChoiceType($this->coreLocator, $this->i18nRuntime);
            $field->add($builder, ['layout' => $options['layout'], 'asDynamic' => $options['asDynamic']]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FieldValue::class,
            'website' => null,
            'layout' => null,
            'field_type' => null,
            'currentForm' => null,
            'asDynamic' => false,
            'translation_domain' => 'admin',
        ]);
    }
}
