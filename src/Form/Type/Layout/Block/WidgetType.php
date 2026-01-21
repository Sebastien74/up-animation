<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Form\Widget as WidgetTypes;
use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WidgetType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WidgetType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * WidgetType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws InvalidArgumentException|MappingException|NonUniqueResultException|\ReflectionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Website $website */
        $website = $options['website'];
        $websiteModel = WebsiteModel::fromEntity($website, $this->coreLocator);
        $allModules = $websiteModel->configuration->modules;
        $gdprActive = $allModules['gdpr'] ?? null;
        $axeptioId = $websiteModel->api->custom->axeptioId;
        $axeptioExternal = $websiteModel->api->custom->axeptioExternal;
        $axeptioActive = $axeptioId || $axeptioExternal;

        $intls = new WidgetTypes\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => ['introduction'],
            'label_fields' => ['introduction' => $this->translator->trans('Script', [], 'admin')],
            'placeholder_fields' => ['introduction' => $this->translator->trans('Saisissez un script', [], 'admin')],
            'help_fields' => ['introduction' => $this->translator->trans("Pensez à utiliser l'attribut <code>title</code> dans une <code>&lt;iframe&gt;</code> pour décrire de manière pertinente son contenu et améliorer l'accessibilité.", [], 'admin')],
        ]);

        if ($axeptioActive) {
            $builder->add('slug', Type\ChoiceType::class, [
                'required' => false,
                'label' => $this->translator->trans('Code Axeptio', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'choices' => [
                    'Social wall' => 'apps-elfsight',
                    'Google maps' => 'gmaps',
                ],
                'attr' => ['group' => 'col-md-3'],
            ]);
        }

        if ($gdprActive) {
            $builder->add('controls', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer la vérification RGPD', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100 mb-0'],
            ]);
        }

        $save = new WidgetTypes\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
