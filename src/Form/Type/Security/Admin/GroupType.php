<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GroupType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupType extends AbstractType
{
    private TranslatorInterface $translator;
    private RouterInterface $router;

    /**
     * GroupType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->router = $this->coreLocator->router();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-9',
        ]);

        if (!$isNew) {
            $builder->add('loginRedirection', ChoiceType::class, [
                'required' => false,
                'label' => $this->translator->trans('Page de redirection à la connexion', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'display' => 'search',
                'choices' => $this->getRoutes(),
                'attr' => ['group' => 'col-md-3'],
            ]);
        }

        $builder->add('roles', EntityType::class, [
            'label' => $this->translator->trans('Rôles', [], 'admin'),
            'class' => Role::class,
            'display' => 'search',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('m')
                    ->orderBy('m.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'multiple' => true,
            'constraints' => [new Assert\Count(min: 1, minMessage: $this->translator->trans('Vous devez sélctionner au moins un groupe.', [], 'security_cms'))],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get routes.
     */
    private function getRoutes(): array
    {
        $results = [];
        $routes = $this->router->getRouteCollection();
        $allowedRoutes = ['admin_form_index'];

        foreach ($routes as $routeName => $route) {
            $isAdminRoute = str_contains($routeName, 'admin_');
            if ($isAdminRoute && str_contains($routeName, '_index')
                || $isAdminRoute && str_contains($routeName, '_layout')
                || $isAdminRoute && str_contains($routeName, '_tree')) {
                if (in_array($routeName, $allowedRoutes) || 1 === preg_match_all('/{/', $route->getPath(), $m)) {
                    $results[$routeName] = $routeName;
                }
            }
        }

        return $results;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
