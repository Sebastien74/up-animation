<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Catalog;

use App\Entity\Module\Catalog\FeatureValue;
use App\Entity\Module\Catalog\FeatureValueProduct;
use App\Entity\Module\Catalog\Product;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

/**
 * FeatureValueAutocompleteField.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsEntityAutocompleteField]
class FeatureValueAutocompleteField extends AbstractType
{
    private TranslatorInterface $translator;
    private ?int $productId = null;

    /**
     * FeatureValueAutocompleteField constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $extraOptions = $this->coreLocator->request()->get('extra_options') ? $this->coreLocator->request()->get('extra_options') : null;
        $decodedJson = $extraOptions ? base64_decode($extraOptions) : null;
        $extraOptions = $decodedJson ? json_decode($decodedJson, true) : null;
        $this->productId = $this->coreLocator->request()->get('catalogproduct') ? (int) $this->coreLocator->request()->get('catalogproduct')
            : (!empty($extraOptions['catalogproduct']) ? (int) $extraOptions['catalogproduct'] : null);

        $resolver->setDefaults([
            'class' => FeatureValue::class,
            'placeholder' => $this->translator->trans('Valeur', [], 'admin'),
            'loading_more_text' => $this->translator->trans("Chargement d'autres résultats...", [], 'admin'),
            'no_results_found_text' => $this->translator->trans('Aucun résultat trouvé', [], 'admin'),
            'no_more_results_text' => $this->translator->trans('Aucun résultat trouvé', [], 'admin'),
            'min_characters' => null,
            'group_by' => 'catalogfeature.adminName',
            'attr' => ['group' => 'mb-0'],
            'data' => null, // Option pour récupérer l'objet passé
            'choice_label' => fn ($entity) => strip_tags($entity->getAdminName()),
            'multiple' => false,
            'searchable_fields' => ['id', 'adminName'],
            'max_results' => 20,
            'tom_select_options' => [
                'maxOptions' => 1000000000,
            ],
            'extra_options' => [
                'catalogproduct' => $this->productId,
            ],
        ]);

        $resolver->setAllowedTypes('data', ['null', FeatureValueProduct::class]);

        $resolver->setNormalizer('query_builder', function (Options $options, $queryBuilder) {

            $featureValueProduct = $options['data'];
            $product = $this->coreLocator->em()->getRepository(Product::class)->findOneBy([
                'id' => $this->productId,
            ]);

            $qb = $this->coreLocator->em()->getRepository(FeatureValue::class)->createQueryBuilder('v')
                ->leftJoin('v.catalogfeature', 'f')
                ->andWhere('v.website = :website')
                ->andWhere('v.slug IS NOT NULL')
                ->andWhere('v.product IS NULL OR v.product = :product')
                ->setParameter('product', $product)
                ->setParameter('website', $this->coreLocator->website()->entity)
                ->addSelect('f');

            if ($featureValueProduct instanceof FeatureValueProduct && $featureValueProduct->getFeature() && !$featureValueProduct->getValue()) {
                $qb->andWhere('f.id = :featureId')
                    ->setParameter('featureId', $featureValueProduct->getFeature()->getId());
            }

            return $qb->orderBy('f.adminName', 'ASC')
                ->addOrderBy('v.adminName', 'ASC');
        });
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
