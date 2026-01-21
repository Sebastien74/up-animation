<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Map;

use App\Entity\Core\Website;
use App\Entity\Media\Folder;
use App\Entity\Module\Map\Category;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;

    /**
     * CategoryType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-10',
        ]);

        if (!$isNew) {
            $builder->add('markerWidth', Type\IntegerType::class, [
                'label' => $this->translator->trans('Largeur du marker (px)', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'group' => 'col-md-4',
                    'data-config' => true,
                ],
            ]);

            $builder->add('markerHeight', Type\IntegerType::class, [
                'label' => $this->translator->trans('Hauteur du marker (px)', [], 'admin'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                    'group' => 'col-md-4',
                    'data-config' => true,
                ],
            ]);

            $builder->add('marker', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Marqueur', [], 'admin'),
                'choices' => $this->getMarkers($options['website']),
                'choice_attr' => function ($dir, $key, $value) {
                    return ['data-background' => strtolower($dir)];
                },
                'attr' => [
                    'group' => 'col-md-2 markers-select',
                    'class' => 'select-icons',
                ],
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'fields' => ['title'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    /**
     * Get markers choices.
     */
    private function getMarkers(Website $website): array
    {
        $mapFolder = $this->entityManager->getRepository(Folder::class)->findOneBy([
            'website' => $website,
            'slug' => 'map',
        ]);

        $markers = [];
        foreach ($mapFolder->getMedias() as $media) {
            $markers[$media->getFilename()] = '/uploads/'.$website->getUploadDirname().'/'.$media->getFilename();
        }

        return $markers;
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
