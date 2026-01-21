<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WebsiteLocalesType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteLocalesType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private RequestStack $requestStack;

    /**
     * WebsiteLocalesType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->requestStack = $this->coreLocator->requestStack();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->translator->trans('Cacher en :', [], 'admin'),
            'required' => false,
            'multiple' => true,
            'choice_translation_domain' => false,
            'choices' => $this->getLocales(),
            'attr' => function (OptionsResolver $attr) {
                $attr->setDefaults([
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'class' => 'select-icons',
                    'group' => 'col-12',
                ]);
            },
            'display' => 'search',
            'choice_attr' => function ($iso, $key, $value) {
                return [
                    'data-image' => '/medias/icons/flags/'.strtolower($iso).'.svg',
                    'data-class' => 'flag mt-min',
                    'data-text' => true,
                    'data-height' => 14,
                    'data-width' => 19,
                ];
            },
            'translation_domain' => 'admin',
        ]);
    }

    /**
     * Get WebsiteModel locales.
     */
    private function getLocales(): array
    {
        $website = $this->entityManager->getRepository(Website::class)->find($this->requestStack->getMainRequest()->get('website'));
        $configuration = $website->getConfiguration();
        $defaultLocale = $configuration->getLocale();
        $locales[Languages::getName($defaultLocale)] = $defaultLocale;
        foreach ($configuration->getLocales() as $locale) {
            $name = empty($locales[Languages::getName($locale)]) ? Languages::getName($locale) : Languages::getName($locale).' ('.strtoupper($locale).')';
            $locales[$name] = $locale;
        }

        return $locales;
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
