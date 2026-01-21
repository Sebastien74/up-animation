<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Entity\Core\Website;
use App\Entity\Media\Folder;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * FolderType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FolderType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private Website $website;

    /**
     * FolderType constructor.
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
        $this->website = $options['website'];

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $this->isInternalUser && !$isNew ? 'col-md-6' : 'col-md-8',
            'slug-internal' => $this->isInternalUser,
        ]);

        $builder->add('parent', EntityType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans('Dossier parent', [], 'admin'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => $this->isInternalUser && !$isNew ? 'col-md-3' : 'col-md-4',
            ],
            'class' => Folder::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('f')
                    ->where('f.website = :website')
                    ->setParameter('website', $this->website)
                    ->orderBy('f.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, [
            'only_save' => true,
            'as_ajax' => true,
            'refresh' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Folder::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
