<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Source;

use Github\Client;
use Symfony\Component\Filesystem\Filesystem;

final readonly class GitHubFetcher
{
    public function __construct(private Client $client, private Filesystem $filesystem) {}

    public function fetch(GitHubRepository $repository, string $target, string $ref = 'HEAD'): string
    {
        if (!class_exists(\ZipArchive::class)) { throw new \RuntimeException('bookworm:fetch requires ext-zip.'); }
        $archive = $this->client->api('repo')->contents()->archive($repository->owner, $repository->name, 'zipball', $ref);
        if (!is_string($archive)) { throw new \RuntimeException(sprintf('GitHub returned no archive for %s.', $repository->slug())); }

        $temporary = sys_get_temp_dir().'/bookworm-'.bin2hex(random_bytes(8));
        $zipPath = $temporary.'.zip';
        $this->filesystem->dumpFile($zipPath, $archive); $this->filesystem->mkdir($temporary);
        $zip = new \ZipArchive();
        if (true !== $zip->open($zipPath) || !$zip->extractTo($temporary)) {
            throw new \RuntimeException(sprintf('Unable to extract GitHub archive for %s.', $repository->slug()));
        }
        $zip->close();
        $entries = array_values(array_filter(scandir($temporary) ?: [], static fn (string $entry): bool => !in_array($entry, ['.', '..'], true)));
        if (1 !== count($entries) || !is_dir($temporary.'/'.$entries[0])) {
            throw new \RuntimeException('GitHub archive did not contain one repository root directory.');
        }
        $this->filesystem->remove($target); $this->filesystem->mkdir(dirname($target));
        $this->filesystem->rename($temporary.'/'.$entries[0], $target);
        $this->filesystem->remove([$temporary, $zipPath]);
        return $target;
    }
}
