<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Module\Catalog\Feature;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Form\Widget as WidgetType;
use App\Repository\Module\Catalog\FeatureValueRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FeatureValueProductType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureValueProductType extends AbstractType
{
    private const bool DISPLAY_ARRAY = false;
    private TranslatorInterface $translator;
    private ?FeatureValueProduct $featureValueProduct;

    /**
     * FeatureValueProductType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->featureValueProduct = $event->getData();
            $form = $event->getForm();
            if ($this->featureValueProduct instanceof FeatureValueProduct) {
                $form->add('value', EntityType::class, [
                    'label' => false,
                    'required' => false,
                    'class' => FeatureValue::class,
                    'group_by' => function ($entity, $key, $index) {
                        return $entity->getCatalogfeature()->getAdminName();
                    },
                    'attr' => [
                        'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    ],
                    'query_builder' => function (FeatureValueRepository $er) {
                        $qb = $er->createQueryBuilder('v')
                            ->leftJoin('v.catalogfeature', 'f')
                            ->andWhere('v.website = :website')
                            ->andWhere('v.slug IS NOT NULL')
                            ->andWhere('v.product IS NULL OR v.product = :product')
                            ->setParameter('product', $this->featureValueProduct->getProduct())
                            ->setParameter('website', $this->coreLocator->website()->entity)
                            ->addSelect('f');
                        if ($this->featureValueProduct->getFeature() && !$this->featureValueProduct->getValue()) {
                            $qb->andWhere('f.id = :featureId')
                                ->setParameter('featureId', $this->featureValueProduct->getFeature()->getId());
                        }
                        return $qb;
                    },
                    'choice_label' => function ($entity) {
                        return strip_tags($entity->getAdminName());
                    },
                    'display' => 'search',
                    'row_attr' => ['class' => 'disabled-floating value-group mb-0'],
                ]);
            } else {
                $form->add('value', FeatureValueAutocompleteField::class, ['data' => $this->featureValueProduct]);
            }
        });

        $builder->add('feature', WidgetType\FeatureType::class);

        $builder->add('adminName', Type\TextType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Éditez la valeur', [], 'admin'),
            ],
        ]);

        $builder->add('addToGlobal', Type\CheckboxType::class, [
            'required' => false,
            'mapped' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Ajouter aux principales', [], 'admin'),
            'attr' => ['class' => 'w-100'],
        ]);

        $builder->add('position', Type\HiddenType::class, [
            'attr' => ['class' => 'input-position input-position-collection'],
        ]);

        if (self::DISPLAY_ARRAY) {
            $builder->add('displayInArray', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Afficher dans un tableau', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100', 'data-config' => true],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FeatureValueProduct::class,
            'website' => null,
            'product' => null,
            'custom_widget' => true,
            'translation_domain' => 'admin',
        ]);
    }
}
