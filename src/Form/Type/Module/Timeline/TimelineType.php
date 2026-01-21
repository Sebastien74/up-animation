<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Timeline;

use App\Entity\Module\Timeline\Step;
use App\Entity\Module\Timeline\Timeline;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TimelineType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TimelineType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * TimelineType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Step $data */
        $data = $builder->getData();
        $isNew = !$data->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'slug' => true,
            'class' => 'refer-code',
        ]);

        $builder->add('displayNumbers', CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Afficher les chiffres', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, [
            'btn_both' => !$isNew,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Timeline::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
