<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Corpus;

final readonly class CorpusRegistry
{
    /** @var array<string, Corpus> */
    private array $corpora;

    /** @param array<string, array<string, mixed>> $config */
    public function __construct(array $config)
    {
        $corpora = [];
        foreach ($config as $name => $values) {
            $corpora[$name] = new Corpus($name, $values['directory'], $values['indexer'],
                $values['repository'] ?? null, $values['canonical_base_url'] ?? null,
                $values['patterns'] ?? ['*.md'], $values['max_chunk_size'] ?? 4000);
        }
        $this->corpora = $corpora;
    }

    public function get(string $name): Corpus
    {
        return $this->corpora[$name] ?? throw new \InvalidArgumentException(sprintf(
            'Unknown Bookworm corpus "%s". Available: %s.', $name,
            implode(', ', array_keys($this->corpora)) ?: '(none)'));
    }

    /** @return array<string, Corpus> */
    public function all(): array { return $this->corpora; }
}
