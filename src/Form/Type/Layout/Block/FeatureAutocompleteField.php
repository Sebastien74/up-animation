<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Module\Catalog\Feature;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

/**
 * FeatureAutocompleteField.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[AsEntityAutocompleteField]
class FeatureAutocompleteField extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FeatureAutocompleteField constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Feature::class,
            'placeholder' => $this->translator->trans('Caractéristique', [], 'admin'),
            'loading_more_text' => $this->translator->trans("Chargement d'autres résultats...", [], 'admin'),
            'no_results_found_text' => $this->translator->trans('Aucun résultat trouvé', [], 'admin'),
            'no_more_results_text' => $this->translator->trans('Aucun résultat trouvé', [], 'admin'),
            'min_characters' => null,
            'attr' => ['group' => 'mb-0'],
            'data' => null, // Option pour récupérer l'objet passé
            'choice_label' => fn ($entity) => strip_tags($entity->getAdminName()),
            'multiple' => false,
            'searchable_fields' => ['id', 'adminName'],
            'max_results' => 20,
            'tom_select_options' => ['maxOptions' => 1000000000],
        ]);

        $resolver->setNormalizer('query_builder', function (Options $options, $queryBuilder) {
            return $this->coreLocator->em()->getRepository(Feature::class)->createQueryBuilder('f')
                ->andWhere('f.website = :website')
                ->setParameter('website', $this->coreLocator->website()->entity)
                ->orderBy('f.adminName', 'ASC');
        });
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
