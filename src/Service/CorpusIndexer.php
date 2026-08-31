<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Service;

use Psr\Container\ContainerInterface;
use Survos\BookwormBundle\Corpus\CorpusRegistry;
use Survos\BookwormBundle\Document\{MarkdownChunkTransformer,MarkdownCorpusLoader};
use Symfony\AI\Store\IndexerInterface;

final readonly class CorpusIndexer
{
    public function __construct(private CorpusRegistry $corpora, private MarkdownCorpusLoader $loader,
        private MarkdownChunkTransformer $chunker, private ContainerInterface $indexers) {}

    public function index(string $name): void
    {
        $corpus = $this->corpora->get($name); $indexer = $this->indexers->get($corpus->indexer);
        if (!$indexer instanceof IndexerInterface) {
            throw new \LogicException(sprintf('Indexer "%s" must implement %s.', $corpus->indexer, IndexerInterface::class));
        }
        $indexer->index($this->chunker->transform($this->loader->load($corpus), $corpus->maxChunkSize));
    }
}
