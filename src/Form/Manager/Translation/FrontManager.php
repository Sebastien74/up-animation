<?php

declare(strict_types=1);

namespace App\Form\Manager\Translation;

use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationDomain;
use App\Entity\Translation\TranslationUnit;
use App\Repository\Translation\TranslationDomainRepository;
use App\Repository\Translation\TranslationRepository;
use App\Service\Http\RequestParam;
use App\Service\Translation\Extractor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * IntlManager.
 *
 * Manage intl admin form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => FrontManager::class, 'key' => 'intl_front_form_manager'],
])]
class FrontManager
{
    private ?Request $request;

    /**
     * FrontManager constructor.
     */
    public function __construct(
        private readonly RequestStack                $requestStack,
        private readonly TranslatorInterface         $translator,
        private readonly EntityManagerInterface      $entityManager,
        private readonly TranslationDomainRepository $domainRepository,
        private readonly TranslationRepository       $translationRepository,
        private readonly Extractor                   $extractor,
    )
    {
        $this->request = $this->requestStack->getMainRequest();
    }

    /**
     * Process to post text (|trans).
     */
    public function postText(): array
    {
        $domain = $this->getDomain();
        $translation = $this->getTranslation($domain);
        $message = null;
        if (!$translation) {
            $message = $this->translator->trans("Deux contenus identiques ont été trouvés. Veuillez éditer via l'administration.", [], 'admin');
        } else {
            $translation->setContent(RequestParam::get($this->request, 'content'));
            $this->entityManager->flush();
            $this->extractor->clearCache();
        }

        return ['success' => false !== $translation, 'message' => $message, 'content' => RequestParam::get($this->request, 'content')];
    }

    /**
     * Get TranslationDomain.
     */
    private function getDomain(): TranslationDomain
    {
        $domain = $this->domainRepository->findOneBy(['name' => RequestParam::get($this->request, 'domain')]);
        if (!$domain) {
            $domain = new TranslationDomain();
            $domain->setAdminName(RequestParam::get($this->request, 'domain'));
            $domain->setName(RequestParam::get($this->request, 'domain'));
            $this->entityManager->persist($domain);
            $this->entityManager->flush();
        }

        return $domain;
    }

    /**
     * Get Translation.
     */
    private function getTranslation(TranslationDomain $domain): bool|Translation|null
    {
        $translations = $this->translationRepository->findByDomainAndKeyName($domain, RequestParam::get($this->request, 'key_name'), RequestParam::get($this->request, 'locale'));
        if (count($translations) > 1) {
            return false;
        }
        $translation = !empty($translations[0]) ? $translations[0] : null;
        if (!$translations) {
            $translation = new Translation();
            $translation->setLocale(RequestParam::get($this->request, 'locale'));
            $translation->setUnit($this->getUnit($domain));
            $this->entityManager->persist($translation);
        }

        return $translation;
    }

    /**
     * Get TranslationUnit.
     */
    private function getUnit(TranslationDomain $domain): TranslationUnit
    {
        $unit = new TranslationUnit();
        $unit->setDomain($domain);
        $unit->setKeyname(RequestParam::get($this->request, 'key_name'));
        $this->entityManager->persist($unit);

        return $unit;
    }
}
