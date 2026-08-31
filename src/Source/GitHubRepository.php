<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Source;

final readonly class GitHubRepository
{
    public function __construct(public string $owner, public string $name) {}

    public static function fromString(string $value): self
    {
        $path = parse_url(str_contains($value, '://') ? $value : 'https://'.$value, PHP_URL_PATH);
        $parts = array_values(array_filter(explode('/', trim((string) $path, '/'))));
        if (count($parts) < 2) {
            throw new \InvalidArgumentException(sprintf('Expected github.com/owner/repository, got "%s".', $value));
        }
        return new self($parts[0], preg_replace('/\.git$/', '', $parts[1]) ?? $parts[1]);
    }

    public function slug(): string { return $this->owner.'/'.$this->name; }
}
