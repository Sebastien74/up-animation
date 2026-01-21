<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\ListingFeatureValue;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ListingFeatureValueType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingFeatureValueType extends AbstractType
{
    private TranslatorInterface $translator;

    private Website $website;

    /**
     * ListingFeatureValueType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->website = $options['website'];

        $builder->add('value', EntityType::class, [
            'label' => false,
            'class' => FeatureValue::class,
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'attr' => ['group' => 'col-12 mb-3'],
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('f')
                    ->where('f.website = :website')
                    ->andWhere('f.adminName IS NOT NULL')
                    ->andWhere('f.slug IS NOT NULL')
                    ->setParameter('website', $this->website)
                    ->orderBy('f.adminName', 'ASC');
            },
            'group_by' => 'catalogfeature.adminName',
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'display' => 'search',
            'constraints' => [new Assert\NotBlank()],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListingFeatureValue::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
