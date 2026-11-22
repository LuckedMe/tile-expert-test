<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Order\OrderSearchIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

use function sprintf;

#[AsCommand(name: 'app:order:reindex', description: 'Rebuild the Manticore orders index from MySQL')]
final class OrderSearchReindexCommand extends Command
{
    public function __construct(
        private readonly OrderSearchIndexer $indexer,
    ) {
        parent::__construct();
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $total = $this->indexer->reindexAll(
            static fn (int $count) => $io->writeln(sprintf('Indexed %d orders...', $count))
        );

        $io->success(sprintf('Done, %d orders indexed.', $total));

        return Command::SUCCESS;
    }
}
