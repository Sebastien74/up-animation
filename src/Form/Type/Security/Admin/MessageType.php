<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Entity\Security\Message;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MessageType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MessageType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * MessageType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
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
                'fields' => ['title', 'body', 'targetLink'],
                'label_fields' => [
                    'title' => $this->translator->trans('Sujet', [], 'admin'),
                    'body' => $this->translator->trans('Message', [], 'admin'),
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
