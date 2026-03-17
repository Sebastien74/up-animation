<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Entity\Media\Folder;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PictogramType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PictogramType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private Website $website;

    /**
     * PictogramType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->website = $this->coreLocator->website()?->entity;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Pictogramme', [], 'admin'),
            'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
            'required' => false,
            'expanded' => false,
            'display' => 'search',
            'multiple' => false,
            'choices' => $this->getPictograms(),
            'choice_attr' => function ($dir, $key, $value) {
                return ['data-background' => strtolower($dir)];
            },
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'data-config' => true,
                    'row_attr' => ['class' => 'col-12 col-md-12 col-lg-3'],
                    'class' => 'col-12 select-icons img-pictograms',
                    'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ]);
            },
        ]);
    }

    /**
     * Get pictograms choices.
     */
    private function getPictograms(): array
    {
        $folder = $this->entityManager->getRepository(Folder::class)->findOneBy([
            'slug' => 'pictogram',
            'website' => $this->website,
        ]);

        $pictograms = [];
        if ($folder) {
            foreach ($folder->getMedias() as $media) {
                $pictograms[$media->getOriginalName()] = '/uploads/'.$this->website->getUploadDirname().'/'.$media->getOriginalName();
            }
        }

        return $pictograms;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
