<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Tests;
use PHPUnit\Framework\TestCase;
use Survos\BookwormBundle\Corpus\CorpusRegistry;

final class CorpusRegistryTest extends TestCase
{
    public function testDefaults(): void
    {
        $corpus = (new CorpusRegistry(['docs' => ['directory' => '/tmp/docs', 'indexer' => 'ai.indexer.docs']]))->get('docs');
        self::assertSame(['*.md'], $corpus->patterns); self::assertSame(4000, $corpus->maxChunkSize);
    }
}
