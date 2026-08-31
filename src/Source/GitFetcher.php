<?php
declare(strict_types=1);
namespace Survos\BookwormBundle\Source;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final readonly class GitFetcher
{
    public function __construct(private Filesystem $filesystem) {}

    public function fetch(string $repository, string $target, string $ref = 'HEAD'): string
    {
        if (is_dir($target.'/.git')) {
            $command = ['git', '-C', $target, 'pull', '--ff-only'];
        } else {
            $this->filesystem->mkdir(dirname($target));
            $command = ['git', 'clone', '--depth', '1'];
            if ('HEAD' !== $ref) { array_push($command, '--branch', $ref); }
            array_push($command, $repository, $target);
        }
        $process = new Process($command); $process->setTimeout(300); $process->mustRun();
        return $target;
    }
}
