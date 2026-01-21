<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newscast;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Newscast;
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
 * NewscastDuplicateType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastDuplicateType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Request $request;
    private bool $isInternalUser;

    /**
     * NewscastDuplicateType constructor.
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
        $adminName->add($builder, ['adminNameGroup' => $multiSites ? 'col-md-6' : 'col-12']);

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
            'attr' => ['group' => $multiSites ? 'col-md-6' : 'd-none'],
        ]);

        $builder->add('newscast', EntityType::class, [
            'mapped' => false,
            'label' => false,
            'display' => false,
            'attr' => ['class' => 'd-none'],
            'class' => Newscast::class,
            'data' => $options['duplicate_entity'],
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Newscast::class,
            'website' => null,
            'duplicate_entity' => null,
            'translation_domain' => 'admin',
            'allow_extra_fields' => true,
        ]);
    }
}
