<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Configuration;
use App\Entity\Core\Website;
use App\Entity\Media\Category;
use App\Entity\Media\Media;
use App\Form\EventListener\Media\VideoListener;
use App\Form\Validator\File;
use App\Form\Validator\FileSize;
use App\Form\Validator\UniqFile;
use App\Form\Validator\UniqFileName;
use App\Repository\Core\WebsiteRepository;
use App\Service\Content\ImageThumbnailInterface;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Content\FileRuntime;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * MediaType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class MediaType extends AbstractType
{
    private const string ACCEPT = '.xlsx, .xls, image/*, .heic, .doc, .docx, .ppt, .pptx, .txt, .pdf, .webmanifest, .mp4, .m4v, .mov, .webm, .vtt, .geojson';
    private const array MIME_TYPES = [
        'image/*',
        'video/*',
        'text/vtt',
        'application/pdf',
        'application/msword',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'application/json',
    ];

    private TranslatorInterface $translator;
    private ?Request $request;

    /**
     * MediaType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly ImageThumbnailInterface $imageThumbnail,
        private readonly FileRuntime $fileRuntime,
        private readonly WebsiteRepository $websiteRepository,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->request = $this->coreLocator->request();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $websiteRequest = $this->request->get('website');
        /** @var Website $website */
        $website = !empty($options['website']) ? $options['website']
            : ($websiteRequest ? $this->websiteRepository->find($websiteRequest) : null);
        $configuration = $website instanceof Website ? $website->getConfiguration() : null;
        $categoriesActivated = $configuration instanceof Configuration && $configuration->isMediasCategoriesStatus();
        $maxSize = $this->getMaxSize($options);

        $builder->add('uploadedFile', FileType::class, [
            'label' => false,
            'mapped' => false,
            'multiple' => $options['multiple'],
            'required' => false,
            'attr' => [
                'onlyMedia' => $options['onlyMedia'],
                'accept' => $options['onlyVideo'] ? ['video/*', 'text/vtt'] : ($options['onlyMp3'] ? 'audio/mpeg' : self::ACCEPT),
                'data-max-size' => $maxSize,
                'placeholder' => $this->translator->trans('Séléctionnez une image', [], 'admin'),
                'class' => !$options['multiple'] ? 'dropify' : 'dropzone-field',
                'group' => !$options['multiple'] ? 'dropify-group' : 'd-none',
                'data-height' => $options['dataHeight'],
            ],
            'constraints' => [
                new FileSize(),
                new File(mimeTypes: $options['onlyVideo'] ? ['video/*', 'text/vtt'] : ($options['onlyMp3'] ? 'audio/mpeg' : self::MIME_TYPES)),
                new UniqFile(),
            ],
        ]);

        if (!empty($options['copyright'])) {
            $builder->add('copyright', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Copyright', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le copyright', [], 'admin'),
                ],
            ]);

            $builder->add('notContractual', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Image non contractuelle', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);
        }

        if ($options['hideHover']) {
            $builder->add('hideHover', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Désactiver le hover', [], 'admin'),
                'attr' => ['class' => 'w-100'],
            ]);
        }

        if (!empty($options['titlePosition'])) {
            $builder->add('titlePosition', Type\ChoiceType::class, [
                'display' => 'search',
                'choices' => [
                    $this->translator->trans('En haut', [], 'admin') => 'top-start',
                    $this->translator->trans('En bas', [], 'admin') => 'bottom-start',
                    $this->translator->trans('À gauche', [], 'admin') => 'left-start',
                    $this->translator->trans('À droite', [], 'admin') => 'right-start',
                    $this->translator->trans('En haut (Centré dans le bloc)', [], 'admin') => 'top-center',
                    $this->translator->trans('En bas (Centré dans le bloc)', [], 'admin') => 'bottom-center',
                    $this->translator->trans('À gauche (Centré dans le bloc)', [], 'admin') => 'left-center',
                    $this->translator->trans('À droite (Centré dans le bloc)', [], 'admin') => 'right-center',
                    $this->translator->trans('En haut (À droite du bloc)', [], 'admin') => 'top-end',
                    $this->translator->trans('En bas (À droite du bloc)', [], 'admin') => 'bottom-end',
                    $this->translator->trans('À gauche (À droite du bloc)', [], 'admin') => 'left-end',
                    $this->translator->trans('À droite (À droite du bloc)', [], 'admin') => 'right-end',
                    $this->translator->trans("Sur l'image", [], 'admin') => 'in-box',
                ],
                'label' => $this->translator->trans('Position du titre', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
            ]);
        }

        if (!empty($options['quality'])) {
            $builder->add('quality', Type\IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Qualité', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez une valeur', [], 'admin'),
                    'min' => 1,
                    'max' => 100,
                ],
                'constraints' => [new Assert\Range(min: 1, max: 100)],
            ]);
        }

        if ($categoriesActivated && !empty($options['categories'])) {
            $builder->add('categories', EntityType::class, [
                'label' => $this->translator->trans('Catégories', [], 'admin'),
                'class' => Category::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('m')
                        ->orderBy('m.adminName', 'ASC');
                },
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
                'required' => false,
                'display' => 'search',
            ]);
        }

        if ($options['edition'] && $options['screen'] || $options['video']) {
            $builder->add('mediaScreens', CollectionType::class, [
                'label' => false,
                'entry_type' => MediaType::class,
                'entry_options' => [
                    'name' => 'col-12',
                    'onlyVideo' => $options['video'],
                    'edition' => !$options['video'],
                    'copyright' => $options['copyright'],
                    'quality' => $options['quality'],
                    'screen' => false,
                ],
            ]);

            if ($options['video']) {
                $builder->addEventSubscriber(new VideoListener($this->coreLocator));
            }
        }

        if ($options['edition']) {
            $builder->add('name', Type\TextType::class, [
                'required' => false,
                'label' => $this->translator->trans('Nom du fichier', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un nom de fichier', [], 'admin'),
                    'group' => !empty($options['name']) ? $options['name'] : 'col-md-6',
                ],
                'constraints' => [
                    new UniqFileName(),
                ],
            ]);

            $intls = new IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => $options['disabledTitle'] ? ['placeholder' => 'col-12'] : ['title', 'placeholder' => 'col-12'],
                'placeholder_fields' => [
                    'title' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                    'placeholder' => $this->translator->trans('Saisissez un titre', [], 'admin'),
                ],
                'label_fields' => [
                    'title' => $this->translator->trans('Titre', [], 'admin'),
                    'placeholder' => $this->translator->trans('Balise ALT', [], 'admin'),
                ],
                'title_force' => false,
            ]);

            if ($options['screen']) {
                $builder->add('save', Type\SubmitType::class, [
                    'label' => $this->translator->trans('Enregistrer', [], 'admin'),
                    'attr' => ['class' => 'btn-info ajax-post refresh'],
                ]);
            }
        }
    }

    /**
     * To get max size.
     */
    private function getMaxSize(array $options): string
    {
        $maxSizeBytes = $this->fileRuntime->formatBytes($this->imageThumbnail->getMaxFileSize());

        return $options['video'] ? '3000M' : str_replace('MB', 'M', $maxSizeBytes);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'translation_domain' => 'admin',
            'website' => null,
            'edition' => false,
            'name' => null,
            'dataHeight' => null,
            'disabledTitle' => false,
            'titlePosition' => false,
            'hideHover' => false,
            'copyright' => false,
            'quality' => false,
            'categories' => false,
            'onlyMp3' => false,
            'onlyVideo' => false,
            'onlyMedia' => false,
            'video' => false,
            'screen' => true,
            'multiple' => false,
        ]);
    }
}
