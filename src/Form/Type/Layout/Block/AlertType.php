<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AlertType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AlertType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AlertType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => ['introduction'],
            'disableTitle' => true,
            'label_fields' => [
                'introduction' => $this->translator->trans('Message', [], 'admin'),
            ],
            'placeholder_fields' => ['introduction' => $this->translator->trans('Saisissez un message', [], 'admin')],
        ]);

        $builder->add('backgroundColorType', WidgetType\AlertColorType::class, [
            'label' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('icon', WidgetType\IconType::class, [
            'attr' => ['class' => 'select-icons', 'group' => 'col-md-4'],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
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
