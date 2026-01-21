<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Icon;
use App\Entity\Core\Website;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IconType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IconType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private array $icons;

    /**
     * IconType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $website = $this->entityManager->getRepository(Website::class)->find($coreLocator->request()->get('website'));
        $this->icons = $this->entityManager->getRepository(Icon::class)->findBy(['configuration' => $website->getConfiguration()]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Icône', [], 'admin'),
            'required' => false,
            'choices' => $this->getIcons(),
            'dropdown_class' => 'icons-selector',
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-md-4',
            ],
            'choice_attr' => function ($icon, $key, $value) {
                return ['data-image' => $icon];
            },
        ]);
    }

    /**
     * Get WebsiteModel icons.
     */
    private function getIcons(): array
    {
        $choices = [];
        $choices[$this->translator->trans('Séléctionnez', [], 'admin')] = '';
        foreach ($this->icons as $icon) {
            $choices[$icon->getPath()] = $icon->getPath();
        }

        return $choices;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
