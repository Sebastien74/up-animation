<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Management;

use App\Entity\Core\Module;
use App\Entity\Layout\Action;
use App\Entity\Layout\Page;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
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
    private string $projectDir;

    /**
     * ActionType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->projectDir = $this->coreLocator->projectDir();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $data = $builder->getData();
        $files = $this->getFiles();
        $isNew = !$data->getId();

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'slug' => true,
            'adminNameGroup' => $isNew ? 'col-12' : 'col-md-6',
            'slugGroup' => 'col-md-3',
        ]);

        if (!$isNew) {
            $builder->add('controller', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Controller', [], 'admin'),
                'display' => 'search',
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
                'constraints' => [new Assert\NotBlank()],
                'choices' => $files->controllers,
                'choice_translation_domain' => false,
            ]);

            $builder->add('action', Type\ChoiceType::class, [
                'label' => $this->translator->trans('Action', [], 'admin'),
                'display' => 'search',
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
                'constraints' => [new Assert\NotBlank()],
                'choices' => $files->methods,
                'choice_translation_domain' => false,
            ]);

            $builder->add('entity', Type\ChoiceType::class, [
                'required' => false,
                'display' => 'search',
                'label' => $this->translator->trans('Filtre', [], 'admin'),
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
                'choices' => $files->entities,
                'choice_translation_domain' => false,
            ]);

            $builder->add('module', EntityType::class, [
                'label' => $this->translator->trans('Module', [], 'admin'),
                'display' => 'search',
                'class' => Module::class,
                'placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                'attr' => ['group' => 'col-md-3'],
                'constraints' => [new Assert\NotBlank()],
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
            ]);

            $builder->add('iconClass', WidgetType\FontawesomeType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-3',
                ],
            ]);

            $builder->add('card', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer le type fiche', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);

            $builder->add('dropdown', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Ajouter à la zone non prioritaire', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_both' => true]);
    }

    /**
     * Get files namespaces.
     */
    private function getFiles(): object
    {
        $projectDir = $this->projectDir;

        $options = [];
        $options = $this->getCoreControllers($options, $projectDir);
        $options = $this->getCoreEntities($options, $projectDir);

        unset($options['methods']['robots']);
        unset($options['methods']['xml']);
        unset($options['methods']['toolBox']);
        unset($options['methods']['trackEmails']);
        unset($options['methods']['preview']);

        return (object) $options;
    }

    /**
     * Get Core Controller.
     */
    private function getCoreControllers(array $options, string $projectDir): array
    {
        $excludesMethods = ['getSubscribedServices', 'block', '__construct', 'setContainer'];
        $dirname = $projectDir.'/src/Controller/Front/Action';
        $finderControllers = new Finder();
        $finderControllers->files()->in($dirname);

        foreach ($finderControllers as $file) {
            $relativeName = $file->getRelativePathname();
            $controllerName = str_replace('.php', '', $relativeName);
            $namespace = 'App\\Controller\\Front\\Action\\'.$controllerName;
            $options['controllers']['Front: '.$controllerName] = $namespace;
            $methods = get_class_methods('\\'.$namespace);

            foreach ($methods as $method) {
                if (!in_array($method, $excludesMethods)) {
                    $options['methods'][$method] = $method;
                }
            }
        }

        $dirname = $projectDir.'/src/Controller/Security/Front';
        $finderControllers = new Finder();
        $finderControllers->files()->in($dirname);

        foreach ($finderControllers as $file) {
            $relativeName = $file->getRelativePathname();
            $controllerName = str_replace('.php', '', $relativeName);
            $namespace = 'App\\Controller\\Security\\Front\\'.$controllerName;
            $options['controllers']['Security: '.$controllerName] = $namespace;
            $methods = get_class_methods('\\'.$namespace);

            foreach ($methods as $method) {
                if (!in_array($method, $excludesMethods)) {
                    $options['methods'][$method] = $method;
                }
            }
        }

        return $options;
    }

    /**
     * Get Core Entities.
     */
    private function getCoreEntities(array $options, string $projectDir): array
    {
        $dirname = $projectDir.'/src/Entity';
        $finderActions = new Finder();
        $finderActions->files()->in($dirname);

        $options['entities']['Layout: ']['Layout\Page'] = Page::class;

        foreach ($finderActions as $file) {
            $explodeEntity = explode('/', $file->getRelativePathname());

            if (empty($explodeEntity[1])) {
                $explodeEntity = explode('\\', $file->getRelativePathname());
            }

            if (!empty($explodeEntity) && !empty($explodeEntity[2])) {
                $className = 'App\\Entity\\'.str_replace('.php', '', $file->getRelativePathname());
                $options['entities']['Actions: '.$explodeEntity[1]][str_replace('.php', '', $explodeEntity[1].'\\'.$explodeEntity[2])] = str_replace('/', '\\', $className);
            }
        }

        return $options;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
