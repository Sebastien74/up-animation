<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Recruitment;

use App\Entity\Module\Form\Form;
use App\Entity\Module\Recruitment\Category;
use App\Entity\Module\Recruitment\Contract;
use App\Entity\Module\Recruitment\Job;
use App\Form\Validator;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * JobType.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class JobType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * JobType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Job $data */
        $job = $builder->getData();
        $isNew = !$job->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, ['adminNameGroup' => 'col-md-3']);

        $builder->add('contract', EntityType::class, [
            'label' => $this->translator->trans('Type de contrat', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'attr' => [
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => 'col-md-3',
            ],
            'class' => Contract::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->orderBy('c.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);

        $builder->add('category', EntityType::class, [
            'label' => $this->translator->trans('Domaine de compétence', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'required' => false,
            'display' => 'search',
            'attr' => [
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'group' => 'col-md-3',
            ],
            'class' => Category::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->orderBy('c.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
        ]);

        if (!$isNew) {
            $builder->add('form', EntityType::class, [
                'label' => $this->translator->trans('Service (formulaire)', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'attr' => [
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'group' => 'col-md-3',
                ],
                'class' => Form::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
            ]);

            $builder->add('date', Type\DateType::class, [
                'required' => false,
                'label' => $this->translator->trans('Date de début', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
            ]);

            $builder->add('place', Type\TextType::class, [
                'label' => $this->translator->trans('Localité', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un lieu', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('zipCode', Type\TextType::class, [
                'label' => !empty($labels['zipCode']) ? $labels['zipCode'] : $this->translator->trans('Code postal', [], 'admin'),
                'required' => false,
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez un code postal', [], 'admin'),
                ],
                'constraints' => [new Validator\ZipCode()],
            ]);

            $builder->add('department', Type\TextType::class, [
                'label' => $this->translator->trans('Département', [], 'admin'),
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un département', [], 'admin'),
                    'group' => 'col-md-3',
                ],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title' => 'col-md-9', 'duration', 'remuneration', 'company', 'diploma', 'drivingLicence', 'introduction' => 'col-md-6 editor', 'body' => 'col-md-6', 'profil'],
                'extra_fields' => [
                    'duration' => [
                        'type' => Type\TextType::class,
                        'required' => false,
                        'label' => $this->translator->trans('Durée du contrat', [], 'admin'),
                        'attr' => [
                            'group' => 'col-md-3',
                            'placeholder' => $this->translator->trans('Saisissez la durée', [], 'admin'),
                        ],
                    ],
                    'remuneration' => [
                        'type' => Type\TextType::class,
                        'required' => false,
                        'label' => $this->translator->trans('Rémuneration', [], 'admin'),
                        'attr' => [
                            'group' => 'col-md-3',
                            'placeholder' => $this->translator->trans('Saisissez la rémuneration', [], 'admin'),
                        ],
                    ],
                    'company' => [
                        'type' => Type\TextType::class,
                        'required' => false,
                        'label' => $this->translator->trans('Entreprise', [], 'admin'),
                        'attr' => [
                            'group' => 'col-md-3',
                            'placeholder' => $this->translator->trans('Saisissez une entreprise', [], 'admin'),
                        ],
                    ],
                    'diploma' => [
                        'type' => Type\TextType::class,
                        'required' => false,
                        'label' => $this->translator->trans('Diplôme', [], 'admin'),
                        'attr' => [
                            'group' => 'col-md-3',
                            'placeholder' => $this->translator->trans('Saisissez un diplôme', [], 'admin'),
                        ],
                    ],
                    'drivingLicence' => [
                        'type' => Type\TextType::class,
                        'required' => false,
                        'label' => $this->translator->trans('Permis de conduire', [], 'admin'),
                        'attr' => [
                            'group' => 'col-md-3',
                            'placeholder' => $this->translator->trans('Saisissez un diplôme', [], 'admin'),
                        ],
                    ],
                    'profil' => [
                        'type' => Type\TextareaType::class,
                        'required' => false,
                        'editor' => true,
                        'label' => $this->translator->trans('Profil', [], 'admin'),
                        'attr' => [
                            'group' => 'col-md-6',
                            'placeholder' => $this->translator->trans('Décrivez le profil', [], 'admin'),
                        ],
                    ],
                ],
                'label_fields' => [
                    'body' => $this->translator->trans('Description du poste', [], 'admin'),
                ],
                'placeholder_fields' => [
                    'body' => $this->translator->trans('Décrivez le poste', [], 'admin'),
                ],
                'title_force' => false,
            ]);

            $urls = new WidgetType\UrlsCollectionType($this->coreLocator);
            $urls->add($builder, ['display_seo' => true]);

            $dates = new WidgetType\PublicationDatesType($this->coreLocator);
            $dates->add($builder);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Job::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
