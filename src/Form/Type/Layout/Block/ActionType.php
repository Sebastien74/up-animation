<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Form\EventListener\Layout\ActionIntlListener;
use App\Form\Widget as WidgetType;
use App\Service\Core\InterfaceHelper;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ActionType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ActionType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly InterfaceHelper $interfaceHelper,
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Block $data */
        $block = $builder->getData();
        $website = $options['website'];
        $allLocales = $website->getConfiguration()->getAllLocales();
        $templates = $this->getTemplates($website, $block);
        $displayTemplates = count($templates) > 1;

        if ($displayTemplates) {
            $builder->add('template', ChoiceType::class, [
                'label' => $this->translator->trans('Template', [], 'admin'),
                'display' => 'search',
                'choices' => $templates,
                'attr' => ['group' => count($allLocales) > 1 ? 'col-12' : 'col-md-2'],
                'constraints' => [new Assert\NotBlank()],
            ]);
        } elseif ($templates) {
            $builder->add('template', HiddenType::class, [
                'data' => $templates[array_key_first($templates)],
            ]);
        }

        $builder->add('actionIntls', CollectionType::class, [
            'label' => false,
            'entry_type' => ActionIntlType::class,
            'entry_options' => [
                'form_data' => $builder->getData(),
                'displayTemplates' => $displayTemplates,
                'website' => $options['website'],
            ],
        ])->addEventSubscriber(new ActionIntlListener($this->coreLocator));

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
    }

    /**
     * Get template.
     *
     * @throws NonUniqueResultException
     */
    private function getTemplates(?Website $website = null, ?Block $block = null): array
    {
        $templates = [];

        if ($website instanceof Website && $block instanceof Block) {
            $action = $block->getAction();
            $interface = $this->interfaceHelper->generate($action->getEntity());

            if (is_array($interface) && !empty($interface['actionCode']) && !empty($interface['entityCode'])) {
                $websiteTemplate = $website->getConfiguration()->getTemplate();
                $dirname = $this->coreLocator->projectDir().'/templates/front/'.$websiteTemplate.'/actions/'.$interface['actionCode'].'/'.$interface['entityCode'];
                $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
                $filesystem = new Filesystem();

                if ($filesystem->exists($dirname)) {
                    $finder = Finder::create();
                    $finder->files()->in($dirname)->depth([0]);

                    foreach ($finder as $file) {
                        if ('file' === $file->getType()) {
                            $templateName = str_replace('.html.twig', '', $file->getFilename());
                            $templates[$this->templateName($templateName)] = $templateName;
                        }
                    }
                }
            }
        }

        if (empty($templates)) {
            $templates[$this->templateName('default')] = 'default';
        }

        ksort($templates);

        return $templates;
    }

    /**
     * Get template name.
     */
    private function templateName(string $fileName): string
    {
        $names['default'] = $this->translator->trans('Défaut', [], 'admin');
        $names['promote-first'] = $this->translator->trans('Mise en avant de la première publication', [], 'admin');
        $names['main'] = $this->translator->trans('Principal', [], 'admin');
        $names['slider'] = $this->translator->trans('Carrousel', [], 'admin');

        return !empty($names[$fileName]) ? $names[$fileName] : $fileName;
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
