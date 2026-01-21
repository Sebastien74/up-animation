<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Agenda;

use App\Entity\Module\Agenda\Agenda;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * AgendaType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class AgendaType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * AgendaType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Agenda $data */
        $data = $builder->getData();
        $isNew = !$data->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'slug' => true,
            'class' => 'refer-code',
        ]);

        if (!$isNew) {
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'target_config' => false,
                'label_fields' => [
                    'introduction' => $this->translator->trans('Information', [], 'admin'),
                    'targetLink' => $this->translator->trans('URL', [], 'admin'),
                ],
                'fields' => ['title', 'introduction', 'targetLink'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Agenda::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
