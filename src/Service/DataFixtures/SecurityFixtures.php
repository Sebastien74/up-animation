<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Security\Message;
use App\Entity\Security\MessageIntl;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * SecurityFixtures.
 *
 * Security ConfigurationModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SecurityFixtures::class, 'key' => 'security_fixtures'],
])]
class SecurityFixtures
{
    /**
     * SecurityFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
    )
    {
    }

    /**
     * Execute.
     *
     * @throws \Exception
     */
    public function execute(Website $website): void
    {
        $this->addWebsiteToMaster($website);
        $this->addConfiguration($website);
        $this->addWebsite($website);
        $this->addMessages($website);
    }

    /**
     * Add ConfigurationModel.
     */
    private function addWebsiteToMaster(Website $website): void
    {
        /** @var User $webmaster */
        $webmaster = $this->coreLocator->em()->getRepository(User::class)->findOneBy(['login' => 'webmaster']);
        if ($webmaster) {
            $webmaster->addWebsite($website);
            $this->coreLocator->em()->persist($webmaster);
        }
    }

    /**
     * Add ConfigurationModel.
     *
     * @throws \Exception
     */
    private function addConfiguration(Website $website): void
    {
        $security = new Security();
        $security->setWebsite($website);
        $security->setSecurityKey($this->coreLocator->alphanumericKey(30));
        $this->coreLocator->em()->persist($security);

        $website->setSecurity($security);
        $this->coreLocator->em()->persist($website);
    }

    /**
     * Add WebsiteModel to customer.
     */
    private function addWebsite(Website $website): void
    {
        /** @var User $customer */
        $customer = $this->coreLocator->em()->getRepository(User::class)->findOneBy(['login' => 'customer']);
        if ($customer) {
            $customer->addWebsite($website);
            $this->coreLocator->em()->persist($customer);
        }
    }

    /**
     * Messages.
     */
    private function messages(): array
    {
        return [
            'confirmation-registration' => [
                'adminName' => "Email de confirmation du mail utilisateur à l'inscription (Front)",
                'fr' => [
                    'subject' => 'Confirmation de votre e-mail',
                ]
            ],
            'webmaster-registration' => [
                'adminName' => 'Email pour le webmaster pour un nouvel inscrit (Front)',
                'fr' => [
                    'subject' => 'Nouvel inscrit',
                ]
            ],
            'password-request' => [
                'adminName' => 'Email pour la réinitialisation du mot de passe utilisateur (Front)',
                'fr' => [
                    'subject' => 'Réinitialisation de votre mot de passe',
                ]
            ],
            'password-expire' => [
                'adminName' => "Email pour l'expiration du mot de passe utilisateur (Front)",
                'fr' => [
                    'subject' => 'Votre mot de passe a expiré',
                ]
            ],
        ];
    }

    /**
     * Add Messages.
     *
     * @throws LoaderError|SyntaxError|RuntimeError
     */
    public function addMessages(Website $website): void
    {
        $configuration = $website->getConfiguration();
        $locales = $configuration->getAllLocales();
        $baseDirname = '/front/default/actions/security/email/';
        $dirname = $this->coreLocator->projectDir().'/templates'.$baseDirname;
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);

        $position = count($this->coreLocator->em()->getRepository(Message::class)->findBy(['website' => $website])) + 1;
        foreach ($this->messages() as $slug => $messageConfig) {
            $existing = $this->coreLocator->em()->getRepository(Message::class)->findOneBy(['slug' => $slug, 'website' => $website]);
            if (!$existing) {
                $message = new Message();
                $message->setSlug($slug);
                $message->setWebsite($website);
                $message->setPosition($position);
                $message->setAdminName($messageConfig['adminName']);
                foreach ($locales as $locale) {
                    $localeMessage = !empty($messageConfig[$locale]) ? $messageConfig[$locale] : false;
                    if ($localeMessage) {
                        $intl = new MessageIntl();
                        $intl->setLocale($locale);
                        $intl->setWebsite($website);
                        if (!empty($localeMessage['subject'])) {
                            $intl->setTitle($localeMessage['subject']);
                        }
                        if (!empty($localeMessage['content'])) {
                            $intl->setBody($localeMessage['content']);
                        } else {
                            $template = $dirname.$slug.'.html.twig';
                            $filesystem = new Filesystem();
                            if ($filesystem->exists($template)) {
                                $html = $this->templating->render($baseDirname.$slug.'.html.twig');
                                if (preg_match('#<td\s+[^>]*id=["\']content-fixtures["\'][^>]*>(.*?)</td>#is', $html, $matches)) {
                                    $intl->setBody($matches[1]);
                                }
                            }
                        }
                        $message->addIntl($intl);
                    }
                }
                $this->coreLocator->em()->persist($message);
                $this->coreLocator->em()->flush();
                ++$position;
            }
        }
    }
}
