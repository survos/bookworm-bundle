<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Document;

use Ecourty\TextChunker\Strategy\{FixedSizeChunkingStrategy,MarkdownChunkingStrategy,ParagraphChunkingStrategy,SentenceChunkingStrategy};
use Ecourty\TextChunker\TextChunker;
use Symfony\AI\Store\Document\{Metadata,TextDocument};
use Symfony\Component\Uid\Uuid;

final class MarkdownChunkTransformer
{
    /** @param iterable<TextDocument> $documents @return iterable<TextDocument> */
    public function transform(iterable $documents, int $maxChunkSize): iterable
    {
        if ($maxChunkSize < 1) { throw new \InvalidArgumentException('Maximum chunk size must be positive.'); }
        foreach ($documents as $document) {
            $headings = []; $index = 0;
            $sections = (new TextChunker())->setText(self::protectCode($document->getContent()))
                ->chunk(new MarkdownChunkingStrategy());
            foreach ($sections as $section) {
                $text = str_replace("\u{E000}", '#', $section->getText());
                $meta = $section->getMetadata();
                $level = $meta['heading_level'] ?? null; $heading = $meta['heading_text'] ?? null;
                if (is_int($level) && is_string($heading)) {
                    $headings = array_slice($headings, 0, $level - 1);
                    $headings[$level - 1] = $heading; $headings = array_values($headings);
                }
                foreach ($this->split($text, $maxChunkSize) as $part) {
                    $content = [] === $headings ? $part : implode(' > ', $headings)."\n\n".$part;
                    $source = $document->getMetadata()->getSource() ?? (string) $document->getId();
                    yield new TextDocument(
                        Uuid::v5(Uuid::fromString('6ba7b811-9dad-11d1-80b4-00c04fd430c8'), $source.'|'.$index)->toRfc4122(),
                        $content, new Metadata([...$document->getMetadata(), Metadata::KEY_PARENT_ID => $document->getId(),
                            Metadata::KEY_TEXT => $content, 'heading' => $heading, 'headingLevel' => $level,
                            'headingPath' => $headings, 'chunkIndex' => $index, 'contentHash' => hash('sha256', $content)]));
                    ++$index;
                }
            }
        }
    }

    /** @return iterable<string> */
    private function split(string $text, int $max): iterable
    {
        if (mb_strlen($text) <= $max) { yield $text; return; }
        $units = [];
        foreach ((new TextChunker())->setText($text)->chunk(new ParagraphChunkingStrategy()) as $paragraph) {
            if (mb_strlen($paragraph->getText()) <= $max) { $units[] = $paragraph->getText(); continue; }
            foreach ((new TextChunker())->setText($paragraph->getText())->chunk(new SentenceChunkingStrategy()) as $sentence) {
                if (mb_strlen($sentence->getText()) <= $max) { $units[] = $sentence->getText(); continue; }
                foreach ((new TextChunker())->setText($sentence->getText())->chunk(new FixedSizeChunkingStrategy($max)) as $fixed) { $units[] = $fixed->getText(); }
            }
        }
        $buffer = '';
        foreach ($units as $unit) {
            $candidate = '' === $buffer ? $unit : $buffer."\n\n".$unit;
            if ('' !== $buffer && mb_strlen($candidate) > $max) { yield $buffer; $buffer = $unit; } else { $buffer = $candidate; }
        }
        if ('' !== $buffer) { yield $buffer; }
    }

    private static function protectCode(string $markdown): string
    {
        $inside = false; $lines = preg_split('/\R/', $markdown) ?: [$markdown];
        foreach ($lines as &$line) {
            if (preg_match('/^\s*(```|~~~)/', $line)) { $inside = !$inside; }
            elseif ($inside && str_starts_with($line, '#')) { $line = "\u{E000}".substr($line, 1); }
        }
        return implode("\n", $lines);
    }
}
