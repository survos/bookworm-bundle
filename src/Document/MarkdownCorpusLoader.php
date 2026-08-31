<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Document;

use Survos\BookwormBundle\Corpus\Corpus;
use Symfony\AI\Store\Document\Loader\MarkdownLoader;
use Symfony\AI\Store\Document\Metadata;
use Symfony\AI\Store\Document\TextDocument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Uid\Uuid;

final readonly class MarkdownCorpusLoader
{
    public function __construct(private MarkdownLoader $markdownLoader) {}

    /** @return iterable<TextDocument> */
    public function load(Corpus $corpus): iterable
    {
        if (!is_dir($corpus->directory)) {
            throw new \InvalidArgumentException(sprintf('Corpus directory "%s" does not exist.', $corpus->directory));
        }
        $root = realpath($corpus->directory) ?: throw new \RuntimeException('Cannot resolve corpus directory.');
        $finder = (new Finder())->files()->in($root)->sortByName();
        foreach ($corpus->patterns as $pattern) { $finder->name($pattern); }
        $namespace = Uuid::fromString('6ba7b811-9dad-11d1-80b4-00c04fd430c8');

        foreach ($finder as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());
            foreach ($this->markdownLoader->load($file->getRealPath()) as $document) {
                if (!$document instanceof TextDocument) { continue; }
                $metadata = new Metadata([...$document->getMetadata(),
                    Metadata::KEY_SOURCE => $path, 'corpus' => $corpus->name,
                    'sourcePath' => $path, 'repository' => $corpus->repository,
                    'canonicalUrl' => $this->canonicalUrl($corpus, $path)]);
                yield new TextDocument(Uuid::v5($namespace, $corpus->name.'|'.$path)->toRfc4122(),
                    $document->getContent(), $metadata);
            }
        }
    }

    private function canonicalUrl(Corpus $corpus, string $path): ?string
    {
        if (null === $corpus->canonicalBaseUrl) { return null; }
        $published = preg_replace('/(?:^|\/)index\.md$/', '', $path);
        $published = preg_replace('/\.md$/', '/', $published ?? $path);
        return rtrim($corpus->canonicalBaseUrl, '/').'/'.ltrim($published ?? '', '/');
    }
}
