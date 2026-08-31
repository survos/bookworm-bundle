<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Corpus;

final readonly class Corpus
{
    /** @param list<string> $patterns */
    public function __construct(
        public string $name,
        public string $directory,
        public string $indexer,
        public ?string $repository = null,
        public ?string $canonicalBaseUrl = null,
        public array $patterns = ['*.md'],
        public int $maxChunkSize = 4000,
    ) {}
}
