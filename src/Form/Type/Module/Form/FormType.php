<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Form;

use App\Entity\Module\Form\Form;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FormType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FormType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * FormType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->isInternalUser = $this->coreLocator->authorizationChecker()->isGranted('ROLE_INTERNAL');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Form $form */
        $form = $builder->getData();
        $isNew = !$form->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser]);

        if (!$isNew && !$form->getStepform()) {

            $bodyHelp = $this->translator->trans('Pour les valeurs personnalisées ajouter %champs-code%', [], 'admin');
            if (!empty($options['fieldsHelp'])) {
                $bodyHelp = $this->translator->trans('<span class="text-underline">Valeurs personnalisées :</span>', [], 'admin');
                foreach ($options['fieldsHelp'] as $label => $slug) {
                    $bodyHelp .= ' <strong class="fw-500">'.$label. ':</strong> %'.$slug.'%,';
                }
            }

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'data_config' => true,
                'fields' => ['title' => 'col-md-6', 'subTitle' => 'col-md-6', 'body', 'introduction' => 'editor', 'placeholder' => 'col-12'],
                'label_fields' => [
                    'title' => $this->translator->trans('Objet du mail de reception', [], 'admin'),
                    'subTitle' => $this->translator->trans('Objet du mail de confirmation', [], 'admin'),
                    'body' => $this->translator->trans('Corps du mail de confirmation', [], 'admin'),
                    'introduction' => $this->translator->trans('Corps du mail au webmaster', [], 'admin'),
                    'placeholder' => $this->translator->trans('Message de remerciement sur le site', [], 'admin'),
                ],
                'placeholder_fields' => [
                    'title' => $this->translator->trans('Saisissez un objet', [], 'admin'),
                    'subTitle' => $this->translator->trans('Saisissez un objet', [], 'admin'),
                    'body' => $this->translator->trans('Saisissez un message', [], 'admin'),
                    'introduction' => $this->translator->trans('Saisissez un message', [], 'admin'),
                    'placeholder' => $this->translator->trans('Saisissez un message', [], 'admin'),
                ],
                'help_fields' => [
                    'body' => $bodyHelp,
                    'introduction' => $bodyHelp,
                ],
            ]);

            $builder->add('configuration', ConfigurationType::class, [
                'label' => false,
                'isNew' => false,
                'website' => $options['website'],
                'entity' => $form->getConfiguration(),
                'attr' => ['data-config' => true],
            ]);
        } elseif (!$isNew) {
            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title' => 'col-md-12'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Form::class,
            'website' => null,
            'fieldsHelp' => [],
            'translation_domain' => 'admin',
        ]);
    }
}
