<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Form\DataTransformer\EmailToUserTransformer;
use App\Repository\Security\UserRepository;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

/**
 * UserSelectTextType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserSelectTextType extends AbstractType
{
    private RouterInterface $router;

    /**
     * UserSelectTextType constructor.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->router = $this->coreLocator->router();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new EmailToUserTransformer(
            $this->userRepository,
            $options['finder_callback']
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'js-autocomplete',
                'data-autocomplete-url' => $this->router->generate('admin_security_utility'),
                'data-autocomplete-key' => 'email',
            ],
            'invalid_message' => "Cet utilisateur n'existe pas",
            'finder_callback' => function (UserRepository $userRepository, string $email) {
                return $userRepository->findOneBy(['email' => $email]);
            },
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attr = $view->vars['attr'];
        $class = isset($attr['class']) ? $attr['class'].' ' : '';
        $class .= 'js-autocomplete';

        $attr['class'] = $class;
        $attr['data-autocomplete-url'] = $this->router->generate('admin_security_utility');
        $attr['data-autocomplete-key'] = 'email';

        $view->vars['attr'] = $attr;
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }
}
