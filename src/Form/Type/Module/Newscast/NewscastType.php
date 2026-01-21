<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Newscast;

use App\Entity\Core\Website;
use App\Entity\Module\Newscast\Category;
use App\Entity\Module\Newscast\Newscast;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * NewscastType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class NewscastType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private bool $isLayoutUser;
    private ?Website $website = null;

    /**
     * NewscastType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isLayoutUser = $user && in_array('ROLE_LAYOUT_NEWSCAST', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Newscast $data */
        $data = $builder->getData();
        $isNew = !$data->getId();
        $this->website = $options['website'];
        $displayCategory = count($this->entityManager->getRepository(Category::class)->findBy(['website' => $this->website])) > 1 || !$data->getCategory();

        $adminNameClass = 'col-md-9';
        if (!$displayCategory) {
            $adminNameClass = 'col-12';
        }

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $adminNameClass,
            'class' => 'refer-code',
        ]);

        $builder->add('category', EntityType::class, [
            'label' => $this->translator->trans('Catégorie', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'display' => 'search',
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => $displayCategory ? 'col-md-3' : 'd-none',
            ],
            'class' => Category::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->andWhere('c.website = :website')
                    ->setParameter(':website', $this->website)
                    ->orderBy('c.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'constraints' => [new Assert\NotBlank()],
        ]);

        if ($isNew && $this->isLayoutUser) {
            $builder->add('customLayout', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Template personnalisé', [], 'admin'),
                'attr' => ['group' => 'col-md-4 mx-auto', 'class' => 'w-100'],
            ]);
        }

        if (!$isNew) {
            $builder->add('author', Type\TextType::class, [
                'label' => $this->translator->trans('Auteur', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-4',
                    'placeholder' => $this->translator->trans('Saisissez un auteur', [], 'admin'),
                ],
            ]);

            $builder->add('promote', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Mettre en avant', [], 'admin'),
                'attr' => ['group' => 'col-md-2 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            if ($data->getCategory() && $data->getCategory()->isAsEvents()) {

                $builder->add('city', Type\TextType::class, [
                    'label' => $this->translator->trans('Localité', [], 'admin'),
                    'required' => false,
                    'attr' => [
                        'group' => 'col-md-4',
                        'placeholder' => $this->translator->trans('Saisissez un lieu', [], 'admin'),
                    ],
                ]);

                $builder->add('startDate', Type\DateTimeType::class, [
                    'required' => false,
                    'label' => $this->translator->trans("Date de début de l'événement", [], 'admin'),
                    'attr' => ['group' => 'col-md-4'],
                    'placeholder' => [
                        'year' => $this->translator->trans('Année', [], 'admin'),
                        'month' => $this->translator->trans('Mois', [], 'admin'),
                        'day' => $this->translator->trans('Jour', [], 'admin'),
                        'hour' => $this->translator->trans('Heure', [], 'admin'),
                        'minute' => $this->translator->trans('Minute', [], 'admin'),
                        'second' => $this->translator->trans('Seconde', [], 'admin'),
                    ],
                ]);

                $builder->add('endDate', Type\DateTimeType::class, [
                    'required' => false,
                    'label' => $this->translator->trans("Date de fin de l'événement", [], 'admin'),
                    'attr' => ['group' => 'col-md-4'],
                    'placeholder' => [
                        'year' => $this->translator->trans('Année', [], 'admin'),
                        'month' => $this->translator->trans('Mois', [], 'admin'),
                        'day' => $this->translator->trans('Jour', [], 'admin'),
                        'hour' => $this->translator->trans('Heure', [], 'admin'),
                        'minute' => $this->translator->trans('Minute', [], 'admin'),
                        'second' => $this->translator->trans('Seconde', [], 'admin'),
                    ],
                ]);
            }

            if (!$data->isCustomLayout()) {
                $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
                $intls->add($builder, [
                    'website' => $options['website'],
                    'title_force' => false,
                    'disableTitle' => true,
                    'fields' => [
                        'title' => 'col-md-8',
                        'subTitle' => 'col-md-4',
                        'introduction',
                        'body',
                        'video',
                        'targetLink' => 'col-md-12 add-title',
                        'targetPage' => 'col-md-4',
                        'targetLabel' => 'col-md-4',
                        'targetStyle' => 'col-md-4',
                        'newTab' => 'col-md-4',
                    ],
                ]);
            }

            $urls = new WidgetType\UrlsCollectionType($this->coreLocator);
            $urls->add($builder, ['display_seo' => true]);

            $dates = new WidgetType\PublicationDatesType($this->coreLocator);
            $dates->add($builder);

            if ($this->isLayoutUser) {
                $builder->add('customLayout', Type\CheckboxType::class, [
                    'required' => false,
                    'display' => 'button',
                    'color' => 'outline-info-darken',
                    'label' => $this->translator->trans('Template personnalisé', [], 'admin'),
                    'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100', 'data-config' => true],
                ]);
            }
        }

        if ($data->isCustomLayout() || $isNew) {
            $save = new WidgetType\SubmitType($this->coreLocator);
            $save->add($builder, ['btn_both' => true, 'btn_add' => true]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Newscast::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
