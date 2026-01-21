<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Front;

use App\Entity\Core\Website;
use App\Entity\Security\UserFront;
use App\Form\Validator\UniqUserEmail;
use App\Form\Validator\UniqUserLogin;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RegistrationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RegistrationType extends AbstractType
{
    private TranslatorInterface $translator;
    private ?Request $request;

    /**
     * RegistrationType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
        $this->request = $this->coreLocator->requestStack()->getCurrentRequest();
    }

    /**
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Website $website */
        $website = $options['website'];
        $websiteModel = \App\Model\Core\WebsiteModel::fromEntity($website, $this->coreLocator);
        $fields = $website->getSecurity()->getFrontRegistrationFields();
        $mainPages = $websiteModel->configuration->pages;
        $legalNotice = !empty($mainPages['legale']['url']) ? $this->request->getSchemeAndHttpHost().'/'.$mainPages['legale']['url'] : null;
        $cgv = !empty($mainPages['cgv']['url']) ? $this->request->getSchemeAndHttpHost().'/'.$mainPages['cgv']['url'] : null;

        $builder->add('profile', ProfileRegistrationType::class, ['fields' => $fields]);

        if (in_array('lastName', $fields)) {

            $builder->add('lastName', Type\TextType::class, [
                'label' => $this->translator->trans('Nom', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez votre nom', [], 'security_cms'),
                    'class' => 'last_name',
                    'group' => 'col-lg-6',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir votre nom.', [], 'security_cms')),
                ],
            ]);
        }

        if (in_array('firstName', $fields)) {
            $builder->add('firstName', Type\TextType::class, [
                'label' => $this->translator->trans('Prénom', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez votre prénom', [], 'security_cms'),
                    'class' => 'first_name',
                    'group' => 'col-lg-6',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir votre prénom.', [], 'security_cms')),
                ],
            ]);
        }

        if ('login' === $_ENV['SECURITY_FRONT_LOGIN_TYPE']) {
            $builder->add('login', Type\TextType::class, [
                'label' => $this->translator->trans("Nom d'utilisateur", [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un nom', [], 'security_cms'),
                    'class' => 'login',
                    'group' => 'col-lg-6',
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translator->trans("Veuillez saisir un nom d'utilisateur.", [], 'security_cms')),
                    new UniqUserLogin(),
                ],
            ]);
        }

        if (in_array('email', $fields)) {
            $builder->add('email', Type\EmailType::class, [
                'label' => $this->translator->trans('E-mail', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un e-mail', [], 'security_cms'),
                    'class' => 'email',
                    'group' => 'col-lg-6',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => $this->translator->trans('Veuillez saisir un email.', [], 'security_cms')]),
                    new UniqUserEmail(),
                ],
            ]);
        }

        if (!$options['disabled_account'] && in_array('plainPassword', $fields)) {
            $builder->add('plainPassword', Type\RepeatedType::class, [
                'label' => false,
                'type' => Type\PasswordType::class,
                'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
                'first_options' => [
                    'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                        'group' => 'col-lg-6',
                        'class' => 'password-checker',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => $this->translator->trans('Veuillez saisir un mot de passe.', [], 'security_cms')]),
                    ],
                ],
                'second_options' => [
                    'label' => $this->translator->trans('Confirmation du mot de passe', [], 'security_cms'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                        'group' => 'col-lg-6',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => $this->translator->trans('Veuillez confirmer votre mot de passe.', [], 'security_cms')]),
                    ],
                ],
                'constraints' => [
                    new Assert\Regex([
                        'message' => $this->translator->trans('Le mot de passe doit comporter au moins 8 caractères, contenir au moins un chiffre, une majuscule et une minuscule.', [], 'security_cms'),
                        'pattern' => '/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{8,}/',
                    ]),
                ],
            ]);
        }

        if (in_array('agreeTerms', $fields)) {
            $builder->add('agreeTerms', Type\CheckboxType::class, [
                'label' => $this->translator->trans("J’accepte les <a href='".$cgv."' target='_blank'>Conditions Générales de Vente</a> et les <a href='".$legalNotice."' target='_blank'>Conditions générales d'utilisation</a>", [], 'security_cms'),
                'help' => $this->translator->trans('Vous devez prendre connaissance des mentions légales et les accepter pour créer votre compte.', [], 'security_cms'),
                'display' => 'custom',
                'attr' => [
                    'group' => 'col-12 agree-terms-group',
                ],
                'label_attr' => [
                    'class' => 'small',
                ],
                'constraints' => [
                    new Assert\IsTrue(['message' => $this->translator->trans('Vous devez accepter les conditions générales.', [], 'security_cms')]),
                ],
            ]);
        }

        $builder->add('locale', Type\HiddenType::class, [
            'data' => $this->request->getLocale(),
            'constraints' => [
                new Assert\NotBlank(['message' => $this->translator->trans('Veuillez choisir une langue.', [], 'security_cms')]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserFront::class,
            'disabled_account' => false,
            'csrf_protection' => false,
            'website' => null,
        ]);
    }
}
