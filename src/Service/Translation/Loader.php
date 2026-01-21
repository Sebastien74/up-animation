<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Model\Core\WebsiteModel;
use App\Repository\Core\WebsiteRepository;
use App\Repository\Translation\TranslationRepository;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Loader.
 *
 * To load translations
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class Loader implements LoaderInterface
{
    private ?WebsiteModel $website = null;
    private bool $cacheSet = false;
    private array $messages = [];

    /**
     * Loader constructor.
     */
    public function __construct(
        private readonly TranslationRepository $translationRepository,
        private readonly WebsiteRepository $websiteRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * load.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException|\ReflectionException
     */
    public function load($resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        if (!$this->website) {
            $host = $this->requestStack->getMainRequest() instanceof RequestStack ? $this->requestStack->getMainRequest()->getHost() : null;
            $this->website = $host ? $this->websiteRepository->findOneByHost($this->requestStack->getMainRequest()->getHost())
                : $this->websiteRepository->findDefault();
        }

        $defaultLocale = $this->website instanceof WebsiteModel ? $this->website->configuration->locale : 'fr';
        $messages = $this->getMessages($domain, $locale);

        $translations = [];
        foreach ($messages as $keyName => $message) {
            $translations[$keyName] = $message;
            if (!$message) {
                $messageToPush = $this->getMessages($domain, $defaultLocale, $keyName);
                $translations[$keyName] = is_array($messageToPush) || empty($messageToPush) ? $keyName : $messageToPush;
            }
        }

        return new MessageCatalogue($locale, [
            $domain => $translations,
        ]);
    }

    /**
     * To get messages by domain and locale.
     */
    private function getMessages(string $domain, string $locale, ?string $keyName = null): string|array
    {
        if (!$this->cacheSet) {
            $messages = $this->translationRepository->findByLocale($locale);
            foreach ($messages as $message) {
                $unit = $message->getUnit();
                $domainDb = $unit->getDomain();
                $this->messages[$domainDb->getName()][$message->getLocale()][$unit->getKeyname()] = $message->getContent();
            }
            $this->cacheSet = true;
        }

        $localeMessages = !empty($this->messages[$domain][$locale]) ? $this->messages[$domain][$locale] : [];

        if ($keyName) {
            return $localeMessages[$keyName] ?? [];
        }

        return !empty($this->messages[$domain][$locale]) ? $this->messages[$domain][$locale] : [];
    }
}
