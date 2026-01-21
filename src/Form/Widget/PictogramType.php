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
        $this->website = $coreLocator->em()->getRepository(Website::class)->find($this->coreLocator->request()->get('website'));
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
                    'group' => 'col-md-3',
                    'class' => 'select-icons img-pictograms',
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
            'website' => $this->website,
            'slug' => 'pictogram',
        ]);

        $pictograms = [];
        if ($folder) {
            foreach ($folder->getMedias() as $media) {
                $pictograms[$media->getFilename()] = '/uploads/'.$this->website->getUploadDirname().'/'.$media->getFilename();
            }
        }

        return $pictograms;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
