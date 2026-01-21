<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Entity\Core\Website;
use App\Entity\Layout\Block;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TemplateBlockType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TemplateBlockType extends AbstractType
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private string $projectDir;
    private ?Request $request;

    /**
     * TemplateBlockType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
        $this->projectDir = $this->coreLocator->projectDir();
        $this->request = $this->coreLocator->request();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $blockRequest = $this->request->get('block');
        $block = $blockRequest ? $this->entityManager->getRepository(Block::class)->find($blockRequest) : null;
        $templates = $this->getTemplates($block);
        $haveCustom = count($templates) > 1;

        if ($block instanceof Block) {
            $resolver->setDefaults([
                'required' => true,
                'label' => $this->translator->trans('Template', [], 'admin'),
                'display' => 'search',
                'choices' => $templates,
                'attr' => ['data-config' => $haveCustom, 'group' => $haveCustom ? 'col-md-4' : 'd-none'],
            ]);
        }
    }

    /**
     * Get front templates.
     */
    private function getTemplates(Block $block): array
    {
        $templates = [];
        $website = $this->entityManager->getRepository(Website::class)->find($this->request->get('website'));
        $blockType = $block->getBlockType()->getSlug();

        if ($website instanceof Website) {
            $frontDir = $this->projectDir.'/templates/front/'.$website->getConfiguration()->getTemplate().'/blocks/'.$blockType.'/';
            $frontDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $frontDir);
            $filesystem = new Filesystem();
            if ($filesystem->exists($frontDir)) {
                $finder = Finder::create();
                $finder->files()->in($frontDir);
                foreach ($finder as $file) {
                    $matches = explode('.', $file->getRelativePathname());
                    $templates[$this->getTemplateName($matches[0], $blockType)] = $matches[0];
                }
            }
        }

        return $templates;
    }

    /**
     * Get template name.
     */
    private function getTemplateName(string $name, string $blockType): string
    {
        /* $names['block_name']['file_name'] */
        $names['link']['default'] = $this->translator->trans('Par défaut', [], 'admin');
        if (!empty($names[$blockType][$name])) {
            return $names[$blockType][$name];
        }

        return ucfirst($name);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
