<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Layout\Page;
use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * SwitchUserSubscriber.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SwitchUserSubscriber implements EventSubscriberInterface
{
    /**
     * SwitchUserSubscriber constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * On switch User Event.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        /** @var User|UserFront $user */
        $user = $event->getTargetUser();
        $request = $event->getRequest();

        if ($request->hasSession() && ($session = $request->getSession())) {
            $session->set('_locale', $user->getLocale());
            $inAdmin = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $request->getUri());
            $redirection = $inAdmin ? $request->getSchemeAndHttpHost().'/admin-'.$_ENV['SECURITY_TOKEN'].'/dashboard' : $request->getUri();
            $response = new RedirectResponse($redirection);
            if ('_exit' === $request->get('_switch_user')) {
                $response->headers->setCookie(Cookie::create('USER_IMPERSONATOR', '0'));
                if (!$inAdmin) {
                    $response->headers->setCookie(Cookie::create('IS_IMPERSONATOR_FRONT', '0'));
                }
            } else {
                if (!$inAdmin) {
                    $website = $this->coreLocator->website();
                    $pageRepository = $this->entityManager->getRepository(Page::class);
                    $securityPage = $website->security->getFrontPageRedirection();
                    $securityPage = !$securityPage ? $pageRepository->findOneBy([
                        'website' => $website->entity,
                        'slug' => 'user-dashboard',
                    ]) : $securityPage;
                    if ($securityPage instanceof Page) {
                        $page = ViewModel::fromEntity($securityPage, $this->coreLocator);
                        $response = new RedirectResponse($this->urlGenerator->generate('front_index_security', ['url' => $page->urlCode]));
                    }
                    $response->headers->setCookie(Cookie::create('IS_IMPERSONATOR_FRONT', '1'));
                }
                $method = $user instanceof User ? 'get'.ucfirst($_ENV['SECURITY_ADMIN_LOGIN_TYPE'])
                    : 'get'.ucfirst($_ENV['SECURITY_FRONT_LOGIN_TYPE']);
                $response->headers->setCookie(Cookie::create('USER_IMPERSONATOR', $user->$method()));
                $response->headers->setCookie(Cookie::create('IS_IMPERSONATOR', '1'));
            }
            $response->send();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
