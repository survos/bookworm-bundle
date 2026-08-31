<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Tests;
use PHPUnit\Framework\TestCase;
use Survos\BookwormBundle\Source\GitHubRepository;

final class GitHubRepositoryTest extends TestCase
{
    public function testUrl(): void
    {
        $repo = GitHubRepository::fromString('https://github.com/symfony/symfony-docs.git');
        self::assertSame('symfony', $repo->owner); self::assertSame('symfony-docs', $repo->name);
    }
}
