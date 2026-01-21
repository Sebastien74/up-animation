<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Module\Catalog\Feature;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FeatureType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FeatureType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * FeatureType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'required' => false,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Sélectionnez une caractéristique', [], 'admin'),
            'class' => Feature::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('f');
            },
            'choice_label' => 'adminName',
            'form_row' => ['class' => 'feature-group'],
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
