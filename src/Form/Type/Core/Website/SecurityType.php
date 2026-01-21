<?php

declare(strict_types=1);

namespace App\Form\Type\Core\Website;

use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Layout\Page;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SecurityType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityType extends AbstractType
{
    private TranslatorInterface $translator;
    private Website $website;

    /**
     * SecurityType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->website = $options['website'];

        $builder->add('secureWebsite', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Site sécurisé', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('headerData', Type\ChoiceType::class, [
            'label' => $this->translator->trans("Données d'entête", [], 'admin'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionner', [], 'admin'),
            ],
            'choices' => [
                'Strict-Transport-Security' => 'strict-transport-security',
                'Permissions-Policy' => 'permissions-policy',
                'Content-Security-Policy' => 'content-security-policy',
                'Referrer-Policy' => 'referrer-policy',
                'Cross-Origin-Embedder-Policy' => 'cross-origin-embedder-policy',
                'Cross-Origin-Resource-Policy' => 'cross-origin-resource-policy',
                'X-XSS-Protection' => 'x-xss-protection',
                'X-UA-Compatible' => 'x-ua-compatible',
                'X-Content-Type-Options nosniff' => 'content-type-options-nosniff',
                'X-Frame-Options DENY' => 'x-frame-options-deny',
                'X-Frame-Options SAMEORIGIN' => 'x-frame-options-sameorigin',
                'X-Permitted-Cross-DomainModel-Policies' => 'x-permitted-cross-domain-policies',
                'Cross-Origin-Opener-Policy' => 'cross-origin-opener-policy',
                'Access-Control-Allow-Origin '.$this->coreLocator->request()->getSchemeAndHttpHost() => 'access-control-allow-origin',
            ],
            'display' => 'search',
            'multiple' => true,
        ]);

        $builder->add('resetPasswordsByGroup', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Modification des mots de passe par groupe', [], 'admin'),
            'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
        ]);

        $builder->add('adminRegistration', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans("Activer l'inscription", [], 'admin'),
            'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('adminRegistrationValidation', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans("Activer la validation administrateur", [], 'admin'),
            'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('adminPasswordSecurity', Type\CheckboxType::class, [
            'required' => false,
            'display' => 'button',
            'color' => 'outline-info-darken',
            'label' => $this->translator->trans('Activer la validaté des mots de passe', [], 'admin'),
            'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
        ]);

        $builder->add('adminPasswordDelay', Type\IntegerType::class, [
            'label' => $this->translator->trans('Validité des mots de passe (nbr jours)', [], 'admin'),
            'attr' => [
                'group' => 'col-md-3',
                'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
            ],
        ]);

        $secureModule = $this->getSecureModule($options['website']);

        if ($secureModule) {
            $builder->add('frontRegistration', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Activer l'inscription", [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('frontRegistrationValidation', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Activer la validation administrateur", [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('frontEmailConfirmation', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans("Activer la confirmation par email", [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('frontEmailWebmaster', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Envoyer un mail au webmaster', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('frontCustomTemplate', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Utiliser le template personnalisé', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('frontPasswordSecurity', Type\CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Activer la validaté des mots de passe', [], 'admin'),
                'attr' => ['group' => 'col-md-3 d-flex align-items-end', 'class' => 'w-100'],
            ]);

            $builder->add('frontPageRedirection', EntityType::class, [
                'required' => false,
                'display' => 'search',
                'multiple' => false,
                'label' => $this->translator->trans('Pages de redirection', [], 'admin'),
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                    'group' => 'col-md-3',
                ],
                'class' => Page::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->leftJoin('p.urls', 'u')
                        ->andWhere('p.website = :website')
                        ->andWhere('p.deletable = :deletable')
                        ->andWhere('u.online = :online')
                        ->setParameter('website', $this->website)
                        ->setParameter('deletable', true)
                        ->setParameter('online', true)
                        ->addSelect('u')
                        ->orderBy('p.adminName', 'ASC');
                },
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
            ]);

            $builder->add('frontPasswordDelay', Type\IntegerType::class, [
                'label' => $this->translator->trans('Validité des mots de passe (nbr jours)', [], 'admin'),
                'attr' => [
                    'group' => 'col-md-3',
                    'placeholder' => $this->translator->trans('Saisissez une durée', [], 'admin'),
                ],
            ]);

            $builder->add('frontRegistrationFields', Type\ChoiceType::class, [
                'label' => $this->translator->trans("Champs d'inscription", [], 'admin'),
                'choices' => [
                    $this->translator->trans('Genre', [], 'admin') => 'gender',
                    $this->translator->trans('Nom', [], 'admin') => 'lastName',
                    $this->translator->trans('Prénom', [], 'admin') => 'firstName',
                    $this->translator->trans('E-mail', [], 'admin') => 'email',
                    $this->translator->trans('Téléphone', [], 'admin') => 'phones',
                    $this->translator->trans('Adresse', [], 'admin') => 'addresses',
                    $this->translator->trans('Mot de passe', [], 'admin') => 'plainPassword',
                    $this->translator->trans('Conditions générales', [], 'admin') => 'agreeTerms',
                    $this->translator->trans('Facebook Connect', [], 'admin') => 'facebookConnect',
                    $this->translator->trans('Google Connect', [], 'admin') => 'googleConnect',
                ],
                'multiple' => true,
                'display' => 'search',
                'attr' => [
                    'data-placeholder' => $this->translator->trans('Séléctionnez', [], 'admin'),
                ],
            ]);
        }
    }

    /**
     * Check if secure pages Module is activated.
     */
    private function getSecureModule(Website $website): bool
    {
        foreach ($website->getConfiguration()->getModules() as $module) {
            if ('secure-page' === $module->getSlug()) {
                return true;
            }
        }

        return false;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Security::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
