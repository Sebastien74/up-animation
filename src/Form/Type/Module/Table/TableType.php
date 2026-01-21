<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Table;

use App\Entity\Module\Table\Table;
use App\Entity\Module\Table\TableIntl;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TableType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TableType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * TableType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['slug-internal' => $this->isInternalUser]);

        if (!$isNew) {
            $builder->add('cols', CollectionType::class, [
                'label' => false,
                'entry_type' => ColType::class,
                'entry_options' => ['website' => $options['website']],
            ]);

            if ($this->isInternalUser) {
                $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
                $intls->add($builder, [
                    'fields' => ['title'],
                    'title_force' => false,
                    'data_class' => TableIntl::class,
                ]);

                $builder->add('headBackgroundColor', WidgetType\BackgroundColorSelectType::class, [
                    'label' => $this->translator->trans("Couleur de fond de l'entête", [], 'admin'),
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-md-4',
                    ],
                ]);

                $builder->add('headColor', WidgetType\AppColorType::class, [
                    'label' => $this->translator->trans("Couleur de la police de l'entête", [], 'admin'),
                    'attr' => [
                        'data-config' => true,
                        'class' => 'select-icons',
                        'group' => 'col-md-4',
                    ],
                ]);

                $builder->add('striped', CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Striped', [], 'admin'),
                    'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100', 'data-config' => true],
                ]);
            }
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Table::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
