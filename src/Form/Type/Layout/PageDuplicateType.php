<?php

declare(strict_types=1);

namespace App\Form\Type\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Form\Widget\AdminNameType;
use App\Repository\Core\WebsiteRepository;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PageDuplicateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PageDuplicateType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Request $request;
    private bool $isInternalUser;

    /**
     * PageDuplicateType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly WebsiteRepository $websiteRepository,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->request = $this->coreLocator->request();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $multiSites = count($this->websiteRepository->findAll()) > 1;
        $adminName = new AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => $multiSites ? 'col-md-4' : 'col-md-6']);

        $builder->add('parent', EntityType::class, [
            'required' => false,
            'display' => 'search',
            'label' => $this->translator->trans('Page parente', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'class' => Page::class,
            'query_builder' => function (EntityRepository $er) {
                $queryBuilder = $er->createQueryBuilder('p')
                    ->leftJoin('p.website', 'w')
                    ->leftJoin('p.urls', 'u')
                    ->andWhere('u.archived = :archived')
                    ->setParameter('archived', false)
                    ->orderBy('p.adminName', 'ASC');
                if (!$this->isInternalUser) {
                    $queryBuilder->andWhere('w.active = :active')
                        ->setParameter('active', true);
                }

                return $queryBuilder;
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'attr' => ['group' => $multiSites ? 'col-md-4' : 'col-md-6'],
        ]);

        $builder->add('website', EntityType::class, [
            'label' => $this->translator->trans('Site', [], 'admin'),
            'display' => 'search',
            'class' => Website::class,
            'query_builder' => function (EntityRepository $er) {
                $queryBuilder = $er->createQueryBuilder('w')
                    ->orderBy('w.adminName', 'ASC');
                if (!$this->isInternalUser) {
                    $queryBuilder->andWhere('w.active = :active')
                        ->setParameter('active', true);
                }

                return $queryBuilder;
            },
            'data' => $this->websiteRepository->find($this->request->get('website')),
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'attr' => ['group' => $multiSites ? 'col-md-4' : 'd-none'],
        ]);

        $builder->add('page', EntityType::class, [
            'mapped' => false,
            'label' => false,
            'display' => false,
            'attr' => ['class' => 'd-none'],
            'class' => Page::class,
            'data' => $options['duplicate_entity'],
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
            'website' => null,
            'duplicate_entity' => null,
            'translation_domain' => 'admin',
            'allow_extra_fields' => true,
        ]);
    }
}
