<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Entity\Core\Module;
use App\Entity\Core\Website;
use App\Entity\Media\Category;
use App\Entity\Security\User;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?User $user;
    private bool $isInternalUser;

    /**
     * CategoryType constructor.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $this->user && in_array('ROLE_INTERNAL', $this->user->getRoles());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-sm-9' : 'col-sm-6',
            'slug-internal' => $this->isInternalUser,
        ]);

        $builder->add('module', EntityType::class, [
            'label' => $this->translator->trans('Module', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez un module', [], 'admin'),
            'required' => false,
            'class' => Module::class,
            'attr' => [
                'group' => 'col-sm-3',
                'placeholder' => $this->translator->trans('Sélectionnez un module', [], 'admin'),
            ],
            'choices' => $this->getModules($options['website']),
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'display' => 'search',
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get Modules[].
     *
     * @throws InvalidArgumentException
     */
    private function getModules(Website $website): array
    {
        $modules = [];
        foreach ($website->getConfiguration()->getModules() as $module) {
            if (in_array($module->getRole(), $this->user->getRoles())) {
                $modules[] = $module;
            }
        }

        return $modules;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
