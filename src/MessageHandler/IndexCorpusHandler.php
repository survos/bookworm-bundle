<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\MessageHandler;
use Survos\BookwormBundle\Message\IndexCorpus;
use Survos\BookwormBundle\Service\CorpusIndexer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class IndexCorpusHandler
{
    public function __construct(private CorpusIndexer $indexer) {}
    public function __invoke(IndexCorpus $message): void { $this->indexer->index($message->corpus); }
}
