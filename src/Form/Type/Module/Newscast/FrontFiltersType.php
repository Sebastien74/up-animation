<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newscast;

use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Listing;
use App\Model\EntityModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FrontFiltersType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FrontFiltersType extends AbstractType
{
    private TranslatorInterface $translator;

    private array $entities;

    /**
     * FrontFiltersType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Listing $listing */
        $listing = $options['listing'];
        $filters = $builder->getData();
        $this->entities = $options['arguments']['allEntities'];
        $isInline = $listing && method_exists($listing,'isFiltersInline') ? $listing->isFiltersInline() : true;

        $builder->add('category', EntityType::class, [
            'required' => false,
            'placeholder' => $isInline ? $this->translator->trans('Tout', [], 'front_form') : $this->translator->trans('Catégorie', [], 'front_form'),
            'label' => $isInline ? $this->translator->trans('Filtrez', [], 'front_form') : false,
            'expanded' => $isInline,
            'display' => $isInline ? 'inline' : 'search',
            'class' => Category::class,
            'data' => !empty($filters['category']) ? $filters['category'] : null,
            'attr' => [
                'class' => $isInline ? 'form-check form-check-inline p-0 m-0' : '',
                'group' => 'col-12 col-md-4 mb-0',
                'reset-btn' => false,
                'display-label' => $isInline,
            ],
            'choice_label' => function ($entity) {
                $entity = EntityModel::fromEntity($entity, $this->coreLocator, ['disabledMedias' => true, 'disabledLayout' => true])->response;
                return strip_tags($entity->intl->title);
            },
            'query_builder' => function (EntityRepository $er) {
                $statement = $er->createQueryBuilder('e');
                $categoryIds = [];
                foreach ($this->entities as $entity) {
                    $category = $entity->getCategory();
                    if ($category && !in_array($category->getId(), $categoryIds)) {
                        $categoryIds[] = $category->getId();
                    }
                }
                if ($categoryIds) {
                    $statement->andWhere('e.id IN (:categoryIds)')
                        ->setParameter('categoryIds', $categoryIds);
                }

                return $statement->orderBy('e.position', 'ASC');
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'listing' => null,
            'teaser' => null,
            'arguments' => [],
            'csrf_protection' => false,
            'translation_domain' => 'front_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
