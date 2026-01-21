<?php

declare(strict_types=1);

namespace App\Form\Type\Media;

use App\Entity\Media\Folder;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SelectFolderType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SelectFolderType extends AbstractType
{
    private ?Request $request;
    private TranslatorInterface $translator;
    private bool $isInternalUser;
    private mixed $website;

    /**
     * SelectFolderType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->request = $this->coreLocator->requestStack()->getCurrentRequest();
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
        $this->website = $this->request->get('website');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('folder', EntityType::class, [
            'label' => $this->translator->trans('Vos dossiers', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'placeholder' => $this->translator->trans('Racine', [], 'admin'),
            'class' => Folder::class,
            'attr' => [
                'class' => 'folder-selector',
                'group' => 'p-0',
            ],
            'query_builder' => function (EntityRepository $er) {
                if ($this->isInternalUser) {
                    return $er->createQueryBuilder('f')
                        ->where('f.website = :website')
                        ->setParameter(':website', $this->website)
                        ->orderBy('f.adminName', 'ASC');
                } else {
                    return $er->createQueryBuilder('f')
                        ->andWhere('f.website = :website')
                        ->andWhere('f.webmaster = :webmaster')
                        ->setParameter(':website', $this->website)
                        ->setParameter(':webmaster', false)
                        ->orderBy('f.adminName', 'ASC');
                }
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Déplacer', [], 'admin'),
            'attr' => ['class' => 'btn-info disable-preloader'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
