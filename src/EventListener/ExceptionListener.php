<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Command\DoctrineCommand;
use App\Entity\Core\Log;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Entity\Seo\NotFoundUrl;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

/**
 * ExceptionListener.
 *
 * Listen to event Exceptions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class ExceptionListener
{
    /**
     * ExceptionListener constructor.
     */
    public function __construct(
        private CoreLocatorInterface $coreLocator,
        private DoctrineCommand $doctrineCommand,
    ) {
    }

    /**
     * onKernelException.
     *
     * @throws InvalidArgumentException
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $allowedIP = $this->coreLocator->checkIP();
        $havePrevious = method_exists($exception, 'getPrevious') && is_object($exception->getPrevious());
        $asUnknownError = 'The HTTP status code "0" is not valid.' === $exception->getMessage();
        if (!$asUnknownError) {
            try {
                $request->getSession()->set('mainExceptionMessage', $exception->getMessage().' in '.$exception->getFile().'at line '.$exception->getLine());
            } catch (\Exception $e) {
            }
        }

        if ($asUnknownError && $request->getSession()->get('mainExceptionMessage') && !$this->isDoctrineUpdateError($exception)) {
            $event->setThrowable(new HttpException(500, $request->getSession()->get('mainExceptionMessage')));
        }

        if ($exception instanceof AccessDeniedHttpException) {
            $this->checkUser($request, $event);
        } elseif ($havePrevious && $exception->getPrevious() instanceof InsufficientAuthenticationException) {
            $event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('front_index')));
        } elseif ($allowedIP && $this->isDoctrineUpdateError($exception)) {
            $logger = new Logger('doctrine-update-errors');
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/doctrine-update-errors.log', 10, Level::Critical));
            $logger->critical($exception->getMessage());
            $this->doctrineCommand->update();
        } elseif ($request->get('_switch_user') && '_exit' === $request->get('_switch_user')) {
            $event->setResponse(new RedirectResponse($request->getSchemeAndHttpHost()));
        } else {
            if ($exception instanceof NotFoundHttpException) {
                try {
                    $response = $this->coreLocator->redirectionService()->execute($request);
                    if ($response['urlRedirection']) {
                        $event->setResponse(new RedirectResponse($response['urlRedirection'], 301));
                    } else {
                        $this->notFound($request);
                    }
                } catch (\Exception $e) {
                }
            } else {
                try {
                    $this->logException();
                } catch (\Exception $e) {
                }
            }

            if ($havePrevious && method_exists($exception->getPrevious(), 'getMessage')) {
                /* If the schema isn't updated or table not existing */
                if ($allowedIP && $this->isDoctrineUpdateError($exception->getPrevious())) {
                    $logger = new Logger('doctrine-update-errors');
                    $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/doctrine-update-errors.log', 10, Level::Critical));
                    $logger->critical($exception->getMessage());
                    $logger->critical($exception->getPrevious()->getMessage());
                    $this->doctrineCommand->update();
                    $event->setResponse(new RedirectResponse($request->getUri()));
                }
            }
        }
    }

    /**
     * To check User.
     */
    private function checkUser(Request $request, ExceptionEvent $event): void
    {
        $inAdmin = $this->coreLocator->inAdmin();
        $userToken = is_object($this->coreLocator->tokenStorage()) && method_exists($this->coreLocator->tokenStorage(), 'getToken') ? $this->coreLocator->tokenStorage()->getToken() : null;
        /** @var User $user */
        $user = is_object($userToken) && method_exists($userToken, 'getUser') ? $userToken->getUser() : null;
        $website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($request->getHost());

        if ($inAdmin && $this->coreLocator->authorizationChecker()->isGranted('IS_IMPERSONATOR')) {
            $session = new Session();
            $session->getFlashBag()->add('warning', 'You have been logged out for the user '.$user->getLogin());
            $event->setResponse(new RedirectResponse($request->getUri().'?_switch_user=_exit'));
        } elseif ($inAdmin && $user instanceof User && $website instanceof Website) {
            $session = new Session();
            $session->getFlashBag()->add('error', 'Access denied!!');
            $event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('admin_dashboard', ['website' => $website->getId()])));
        }
    }

    /**
     * Add 404 database.
     *
     * @throws \Exception
     */
    private function notFound(Request $request): void
    {
        $excluded = ['build', '_wdt', 'front', 'accueil', '&', '.js', 'admin', 'images', '/www.'];
        $register = true;
        foreach ($excluded as $item) {
            if (str_contains($request->getUri(), $item)) {
                $register = false;
                break;
            }
        }

        if ($register) {
            $website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($request->getHost());
            $type = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $request->getUri()) ? 'admin' : 'front';
            $category = $this->asResources($request->getUri()) ? 'resource' : 'url';
            $existing = $this->coreLocator->em()->getRepository(NotFoundUrl::class)->findOneBy([
                'website' => $website->entity,
                'url' => $request->getUri(),
                'uri' => $request->getRequestUri(),
                'type' => $type,
                'category' => $category,
            ]);

            if (!$existing) {
                $newNotFound = new NotFoundUrl();
                $newNotFound->setUrl($request->getUri());
                $newNotFound->setUri($request->getRequestUri());
                $newNotFound->setType($type);
                $newNotFound->setCategory($category);
                $newNotFound->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
                if ($website) {
                    $newNotFound->setWebsite($website->entity);
                }
                $this->coreLocator->em()->persist($newNotFound);
                $this->coreLocator->em()->flush();
            }
        }
    }

    /**
     * Log one entry Exception log per for alert in back.
     *
     * @throws \Exception
     */
    private function logException(): void
    {
        /** @var Log $lastLog */
        $lastLog = $this->coreLocator->em()->getRepository(Log::class)->findLast();
        $currentUser = $this->coreLocator->tokenStorage()->getToken() ? $this->coreLocator->tokenStorage()->getToken()->getUser() : null;
        $user = !$currentUser instanceof User ? $this->coreLocator->em()->getRepository(User::class)->findOneBy(['login' => 'webmaster']) : $currentUser;

        if (!$lastLog) {
            $log = new Log();
            $log->setCreatedBy($user);
            $this->coreLocator->em()->persist($log);
            $this->coreLocator->em()->flush();
        } else {
            $lastLogDate = $lastLog->getCreatedAt() instanceof \DateTime ? $lastLog->getCreatedAt() : new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $currentDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $diff = $lastLogDate->diff($currentDate);
            if ($diff->days > 0 && 0 === $diff->invert) {
                $log = new Log();
                $log->setCreatedBy($user);
                $this->coreLocator->em()->persist($log);
                $this->coreLocator->em()->flush();
            }
        }
    }

    /**
     * Check if is resource url.
     */
    private function asResources(string $uri): bool
    {
        $imgExtensions = ['.jpg', '.JPG', '.jpeg', '.JPEG', '.png', '.PNG', '.gif', '.GIF', '.ico', '.svg'];
        $archiveExtensions = ['.zip', '.ZIP', '.rar', '.RAR', '.gz', '.GZ', '.7z', '.7Z'];
        $fileExtensions = ['.pdf', '.docx', '.xlsx', '.txt'];
        $resources = ['media\/cache', '\/build\/', '\/.git\/', '\/html\/render', 'bundles\/fosjsrouting'];
        $patterns = array_merge($imgExtensions, $archiveExtensions, $fileExtensions, $resources);
        foreach ($patterns as $pattern) {
            if (preg_match('/'.$pattern.'/', $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if is doctrine error for update.
     */
    private function isDoctrineUpdateError(mixed $exception): bool
    {
        if ($exception instanceof InvalidFieldNameException) {
            return true;
        }

        $patterns = ['Entity of type', 'SQLSTATE', 'Column not found'];
        $excludedPatterns = ['Disk full', '42000', '23000', 'SQL syntax', 'Syntax error', 'server has gone away', 'Access denied', 'is not allowed to connect', 'check the manual', 'max_user_connections', 'connexion'];
        if (method_exists($exception, 'getMessage')) {
            foreach ($excludedPatterns as $pattern) {
                if (str_contains(strtolower($exception->getMessage()), strtolower($pattern))) {
                    return false;
                }
            }
            foreach ($patterns as $pattern) {
                if (preg_match('/'.$pattern.'/', $exception->getMessage()) && !str_contains($exception->getMessage(), '23000')) {
                    return true;
                }
            }
        }

        return false;
    }
}