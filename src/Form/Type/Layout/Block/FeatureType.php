<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Entity\Module\Catalog\Feature;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
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
     * CounterType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $builder->getData()->getData();

        $feature = !empty($data['featureId']) ? $this->coreLocator->em()->getRepository(Feature::class)->find($data['featureId']) : null;
        $builder->add('feature', FeatureAutocompleteField::class, [
            'label' => $this->translator->trans('Caractéristique', [], 'admin'),
            'mapped' => false,
            'data' => $feature,
            'attr' => ['group' => 'col-md-4 mb-4'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true, 'btn_both_label' => $this->translator->trans('Enregistrer et retourner à la mise en page', [], 'admin')]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
