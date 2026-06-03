<?php

declare(strict_types=1);

namespace App\Service\Translation;

use App\Entity\Core\Website;
use App\Entity\Translation\Translation;
use App\Entity\Translation\TranslationDomain;
use App\Entity\Translation\TranslationUnit;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * MachineTranslationService.
 *
 * Translates a batch of collected items through the provider chain and persists
 * them (intl fields or translation units). Idempotent: never overwrites filled content.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class MachineTranslationService
{
    private const int FLUSH_EVERY = 50;

    public function __construct(
        private readonly TranslatorChain $chain,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $translationLogger,
    ) {
    }

    /**
     * @param array<string, mixed> $payload {type, locale, group, class?, items:[{ref, source, html}]}
     */
    public function translateAndPersist(Website $website, array $payload): int
    {
        $defaultLocale = $website->getConfiguration()->getLocale();
        $locale = (string) ($payload['locale'] ?? '');
        $items = \is_array($payload['items'] ?? null) ? $payload['items'] : [];
        if ('' === $locale || !$items) {
            return 0;
        }

        $translated = $this->translateItems($items, $defaultLocale, $locale);
        if (!$translated) {
            return 0;
        }

        return 'intl' === ($payload['type'] ?? null)
            ? $this->persistIntl((string) ($payload['class'] ?? ''), $items, $translated)
            : $this->persistTranslations((string) ($payload['group'] ?? ''), $locale, $items, $translated);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, string> translated text indexed by item index
     */
    private function translateItems(array $items, string $source, string $target): array
    {
        $partitions = [true => [], false => []];
        foreach ($items as $index => $item) {
            $partitions[!empty($item['html'])][] = $index;
        }

        $translated = [];
        foreach ($partitions as $isHtml => $indexes) {
            if (!$indexes) {
                continue;
            }
            $sources = array_map(static fn (int $index): string => (string) $items[$index]['source'], $indexes);
            $result = $this->chain->translate($sources, $source, $target, (bool) $isHtml);
            if (\count($result) !== \count($sources)) {
                continue;
            }
            foreach ($indexes as $position => $index) {
                $translated[$index] = $result[$position];
            }
        }

        return $translated;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string>               $translated
     */
    private function persistIntl(string $class, array $items, array $translated): int
    {
        if (!$this->isIntlClass($class)) {
            $this->translationLogger->warning(sprintf('Rejected intl class "%s".', $class));

            return 0;
        }

        $metadata = $this->entityManager->getClassMetadata($class);
        $count = 0;
        foreach ($translated as $index => $text) {
            $ref = $items[$index]['ref'] ?? [];
            $id = (int) ($ref['id'] ?? 0);
            $field = (string) ($ref['field'] ?? '');
            if (!$id || !\in_array($field, $metadata->getFieldNames(), true)) {
                continue;
            }
            $entity = $this->entityManager->find($class, $id);
            $setter = 'set'.ucfirst($field);
            if (!$entity || !method_exists($entity, $setter)) {
                continue;
            }
            $entity->$setter($text);
            $this->entityManager->persist($entity);
            if (0 === ++$count % self::FLUSH_EVERY) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();

        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string>               $translated
     */
    private function persistTranslations(string $domainName, string $locale, array $items, array $translated): int
    {
        $domain = $this->entityManager->getRepository(TranslationDomain::class)->findOneBy(['name' => $domainName]);
        if (!$domain) {
            return 0;
        }

        $unitRepository = $this->entityManager->getRepository(TranslationUnit::class);
        $translationRepository = $this->entityManager->getRepository(Translation::class);
        $count = 0;
        foreach ($translated as $index => $text) {
            $keyName = (string) ($items[$index]['ref']['keyName'] ?? '');
            if ('' === $keyName) {
                continue;
            }
            $unit = $unitRepository->findOneBy(['domain' => $domain, 'keyName' => $keyName]);
            if (!$unit) {
                continue;
            }
            $translation = $translationRepository->findOneBy(['unit' => $unit, 'locale' => $locale]);
            if ($translation && $translation->getContent()) {
                continue;
            }
            if (!$translation) {
                $translation = new Translation();
                $translation->setLocale($locale);
                $translation->setUnit($unit);
            }
            $translation->setContent($text);
            $this->entityManager->persist($translation);
            if (0 === ++$count % self::FLUSH_EVERY) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();

        return $count;
    }

    private function isIntlClass(string $class): bool
    {
        if (!str_ends_with($class, 'Intl') || !class_exists($class)) {
            return false;
        }
        try {
            $this->entityManager->getClassMetadata($class);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
