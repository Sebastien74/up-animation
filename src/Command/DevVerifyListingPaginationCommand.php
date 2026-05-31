<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Core\Website;
use App\Entity\Module\Catalog\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCommand(
    name: 'app:dev:verify-listing-pagination',
    description: 'Parity check (P1): in-memory array pagination vs SQL QueryBuilder pagination on a representative catalog listing query.'
)]
final class DevVerifyListingPaginationCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface $paginator,
        private readonly RequestStack $requestStack,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('website', 'w', InputOption::VALUE_REQUIRED, 'Website id (default: the website with the most products)')
            ->addOption('locale', 'l', InputOption::VALUE_REQUIRED, 'Locale used for the intl join', 'fr')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Items per page (small value exercises page boundaries)', '5');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = (string) $input->getOption('locale');
        $limit = max(1, (int) $input->getOption('limit'));

        $request = Request::create('https://up.local/');
        $request->setLocale($locale);
        $this->requestStack->push($request);

        $website = $this->resolveWebsite($input->getOption('website'));
        if (!$website) {
            $io->error('No website found.');

            return Command::FAILURE;
        }

        $io->title(sprintf('Listing pagination parity - website #%d, locale "%s", limit %d', $website->getId(), $locale, $limit));

        // Without tiebreaker: mirrors the current findByListing ordering (single column, ties undefined).
        $loose = $this->compare(fn () => $this->productQueryBuilder($website, $locale, false), $limit);
        // With deterministic tiebreaker on id: the fix that makes SQL LIMIT pagination stable.
        $strict = $this->compare(fn () => $this->productQueryBuilder($website, $locale, true), $limit);

        $io->section('Order by position only (current behaviour)');
        $this->report($io, $loose);

        $io->section('Order by position, then id (deterministic tiebreaker)');
        $this->report($io, $strict);

        if ($strict['total'] === 0) {
            $io->warning('Dataset is empty for this scope: parity is trivially true. Run against a populated website for a meaningful check.');
        }

        if ($strict['mismatchPages'] === []) {
            $io->success(sprintf('Deterministic ordering: array and SQL pagination agree on all %d page(s).', $strict['pages']));

            return Command::SUCCESS;
        }

        $io->error(sprintf('Deterministic ordering still diverges on page(s): %s', implode(', ', $strict['mismatchPages'])));

        return Command::FAILURE;
    }

    private function resolveWebsite(mixed $id): ?Website
    {
        if ($id) {
            return $this->entityManager->getRepository(Website::class)->find((int) $id);
        }

        $row = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(p.website) AS websiteId, COUNT(p.id) AS total')
            ->from(Product::class, 'p')
            ->groupBy('p.website')
            ->orderBy('total', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $row ? $this->entityManager->getRepository(Website::class)->find((int) $row['websiteId']) : null;
    }

    private function productQueryBuilder(Website $website, string $locale, bool $tiebreaker): QueryBuilder
    {
        $qb = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.website', 'w')->addSelect('w')
            ->andWhere('e.website = :website')->setParameter('website', $website)
            ->leftJoin('e.intls', 'intl')->andWhere('intl.locale = :locale')->setParameter('locale', $locale)->addSelect('intl')
            ->leftJoin('e.mediaRelations', 'mr')->addSelect('mr')
            ->orderBy('e.position', 'ASC');

        if ($tiebreaker) {
            $qb->addOrderBy('e.id', 'ASC');
        }

        return $qb;
    }

    /**
     * @param callable():QueryBuilder $build
     *
     * @return array{total:int, pages:int, mismatchPages:array<int>}
     */
    private function compare(callable $build, int $limit): array
    {
        $fullArray = $build()->getQuery()->getResult();
        $total = count($fullArray);
        $pages = $total > 0 ? (int) ceil($total / $limit) : 0;
        $mismatchPages = [];

        for ($page = 1; $page <= $pages; ++$page) {
            $arrayIds = $this->ids($this->paginator->paginate($fullArray, $page, $limit));
            $sqlIds = $this->ids($this->paginator->paginate($build()->getQuery(), $page, $limit, ['wrap-queries' => true]));

            if ($arrayIds !== $sqlIds) {
                $mismatchPages[] = $page;
            }
        }

        return ['total' => $total, 'pages' => $pages, 'mismatchPages' => $mismatchPages];
    }

    /**
     * @return array<int>
     */
    private function ids(iterable $pagination): array
    {
        $ids = [];
        foreach ($pagination as $item) {
            $ids[] = $item->getId();
        }

        return $ids;
    }

    /**
     * @param array{total:int, pages:int, mismatchPages:array<int>} $result
     */
    private function report(SymfonyStyle $io, array $result): void
    {
        $io->writeln(sprintf('  entities: %d, pages: %d', $result['total'], $result['pages']));
        if ($result['mismatchPages'] === []) {
            $io->writeln('  <info>identical on every page</info>');

            return;
        }
        $io->writeln(sprintf('  <comment>divergence on page(s): %s</comment>', implode(', ', $result['mismatchPages'])));
    }
}
