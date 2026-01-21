<?php

declare(strict_types=1);

namespace App\Form\EventListener;

use App\Model\Core\WebsiteModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * BaseListener.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
abstract class BaseListener implements EventSubscriberInterface
{
    protected ?WebsiteModel $website;
    protected ?EntityManagerInterface $entityManager;
    protected string $defaultLocale = '';
    protected array $locales = [];
    protected FormEvent $event;

    /**
     * BaseListener constructor.
     */
    public function __construct(
        protected readonly CoreLocatorInterface $coreLocator,
        protected array $options = [],
    ) {
        $this->website = $this->coreLocator->website();
        $this->entityManager = !empty($options['entityManager']) ? $options['entityManager'] : null;
        $configuration = $this->website->configuration;
        $this->defaultLocale = $configuration->locale;
        $this->locales = $configuration->allLocales;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }

    /**
     * preSetData.
     */
    abstract protected function preSetData(FormEvent $event);

    /**
     * onPreSetData.
     */
    public function onPreSetData(FormEvent $event): void
    {
        $this->preSetData($event);
    }
}
