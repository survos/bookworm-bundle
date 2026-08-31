<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Command;

use Survos\BookwormBundle\Corpus\CorpusRegistry;
use Survos\BookwormBundle\Message\IndexCorpus;
use Survos\BookwormBundle\Service\CorpusIndexer;
use Survos\BookwormBundle\Source\{GitHubFetcher,GitHubRepository};
use Symfony\Component\Console\Attribute\{Argument,AsCommand,Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class BookwormService
{
    public function __construct(private CorpusRegistry $corpora, private CorpusIndexer $indexer,
        private MessageBusInterface $bus, private ?GitHubFetcher $githubFetcher, private string $projectDir) {}

    #[AsCommand('bookworm:index', 'Index a configured Markdown corpus through Messenger')]
    public function index(SymfonyStyle $io, #[Argument('configured corpus name')] string $corpus,
        #[Option('run indexing in this process instead of dispatching it')] bool $sync = false): int
    {
        $this->corpora->get($corpus);
        if ($sync) { $this->indexer->index($corpus); $io->success(sprintf('Indexed "%s".', $corpus)); }
        else { $this->bus->dispatch(new IndexCorpus($corpus)); $io->success(sprintf('Dispatched "%s".', $corpus)); }
        return Command::SUCCESS;
    }

    #[AsCommand('bookworm:status', 'List configured Markdown corpora')]
    public function status(SymfonyStyle $io): int
    {
        $rows = [];
        foreach ($this->corpora->all() as $corpus) {
            $rows[] = [$corpus->name, $corpus->directory, $corpus->indexer, is_dir($corpus->directory) ? 'ready' : 'missing'];
        }
        $io->table(['Corpus', 'Directory', 'Indexer', 'Source'], $rows);
        return Command::SUCCESS;
    }

    #[AsCommand('bookworm:fetch', 'Fetch a GitHub documentation repository into the local Bookworm cache')]
    public function fetch(SymfonyStyle $io,
        #[Argument('GitHub URL, e.g. github.com/symfony/symfony-docs')] string $repository,
        #[Option('branch, tag, or commit')] string $ref = 'HEAD',
        #[Option('target directory; defaults to var/bookworm/<owner>/<repository>')] ?string $target = null): int
    {
        if (null === $this->githubFetcher) {
            $io->error('Install knplabs/github-api plus a PSR-18 client to enable bookworm:fetch.');
            return Command::FAILURE;
        }
        $github = GitHubRepository::fromString($repository);
        $target ??= $this->projectDir.'/var/bookworm/'.$github->slug();
        $path = $this->githubFetcher->fetch($github, $target, $ref);
        $io->success(sprintf('Fetched %s@%s to %s.', $github->slug(), $ref, $path));
        return Command::SUCCESS;
    }
}
