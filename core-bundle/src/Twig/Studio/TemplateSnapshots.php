<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio;

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

/**
 * Short-lived, whole-tree history for user templates. The Git directory lives in
 * the cache and is deliberately separate from the project's repository.
 */
final class TemplateSnapshots
{
    private readonly string $workTree;
    private readonly string $gitDir;

    public function __construct(
        string $projectDir,
        string $cacheDir,
        private readonly CacheInvalidator $cacheInvalidator,
        private readonly ContaoFilesystemLoader $loader,
    ) {
        $this->workTree = Path::join($projectDir, 'templates');
        $this->gitDir = Path::join($cacheDir, 'template-snapshots', '.git');
    }

    public function isAvailable(): bool
    {
        try {
            return 0 === new Process(['git', '--version'])->run();
        } catch (\Throwable) {
            return false;
        }
    }

    public function snapshot(string $message = 'Manual snapshot'): string
    {
        return $this->locked(
            function () use ($message): string {
                $this->initialize();

                return $this->commit('Template snapshot: '.preg_replace('/[\r\n]+/', ' ', trim($message)));
            },
        );
    }

    /**
     * @return list<array{hash: string, date: string, message: string}>
     */
    public function listSnapshots(): array
    {
        return $this->locked(
            function (): array {
                if (!$this->hasHistory()) {
                    return [];
                }

                $output = $this->git(['log', '-50', '--format=%H%x00%aI%x00%s%x00']);
                $snapshots = [];

                foreach (explode("\n", trim($output)) as $line) {
                    [$hash, $date, $message] = array_pad(explode("\0", $line), 3, '');

                    if (!str_starts_with($message, 'Template snapshot: ')) {
                        continue;
                    }

                    $snapshots[] = [
                        'hash' => $hash,
                        'date' => $date,
                        'message' => $message,
                    ];
                }

                return $snapshots;
            },
        );
    }

    /**
     * @return array{hash: string, date: string}|null
     */
    public function latestSnapshot(): array|null
    {
        return $this->locked(
            function (): array|null {
                if (!$this->hasHistory()) {
                    return null;
                }

                $output = trim($this->git(['log', '-1', '--format=%H%x00%aI', '--grep=^Template snapshot: ']));

                if ('' === $output) {
                    return null;
                }

                [$hash, $date] = explode("\0", $output, 2);

                return ['hash' => $hash, 'date' => $date];
            },
        );
    }

    public function diff(string|null $hash = null): string
    {
        return $this->locked(
            function () use ($hash): string {
                $target = $this->resolve($hash);
                $this->git(['add', '-A', '-f']);

                return $this->git(['diff', '--cached', '--no-ext-diff', '--no-renames', $target, '--', '.']);
            },
        );
    }

    /**
     * @return array{restored: string}
     */
    public function rollback(string|null $hash = null): array
    {
        return $this->locked(
            function () use ($hash): array {
                $target = $this->resolve($hash);
                $this->cacheInvalidator->invalidateCache('');
                $this->git(['read-tree', '--reset', '-u', $target]);
                $this->loader->warmUp(true);
                $this->cacheInvalidator->invalidateCache('');

                return ['restored' => $target];
            },
        );
    }

    private function initialize(): void
    {
        if (is_dir($this->gitDir)) {
            return;
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->workTree);
        $filesystem->mkdir(\dirname($this->gitDir));

        $this->run(['git', 'init', '--bare', $this->gitDir]);
    }

    private function hasHistory(): bool
    {
        return is_dir($this->gitDir) && 0 === $this->run($this->command(['rev-parse', '--verify', 'HEAD']), false)->getExitCode();
    }

    private function resolve(string|null $hash): string
    {
        if (!$this->hasHistory()) {
            throw new TemplateSnapshotException('No template snapshots are available.');
        }

        if (null === $hash || '' === $hash) {
            $hash = trim($this->git(['log', '-1', '--format=%H', '--grep=^Template snapshot: ']));

            if ('' === $hash) {
                throw new TemplateSnapshotException('No manual template snapshot is available.');
            }
        }

        if (!preg_match('/^[a-f0-9]{40,64}$/i', $hash)) {
            throw new TemplateSnapshotException('Invalid template snapshot hash.');
        }

        $resolved = trim($this->git(['rev-parse', '--verify', $hash.'^{commit}']));

        if (0 !== $this->run($this->command(['merge-base', '--is-ancestor', $resolved, 'HEAD']), false)->getExitCode()) {
            throw new TemplateSnapshotException('The template snapshot is not in this history.');
        }

        return $resolved;
    }

    private function commit(string $message): string
    {
        $this->git(['add', '-A', '-f']);
        $this->git(['-c', 'user.name=Contao', '-c', 'user.email=contao@localhost', 'commit', '--allow-empty', '-m', $message]);

        return trim($this->git(['rev-parse', 'HEAD']));
    }

    private function git(array $arguments): string
    {
        return $this->run($this->command($arguments))->getOutput();
    }

    private function command(array $arguments): array
    {
        return [
            'git', '--git-dir='.$this->gitDir, '--work-tree='.$this->workTree,
            '-c', 'core.bare=false', '-c', 'core.hooksPath=/dev/null',
            ...$arguments,
        ];
    }

    private function run(array $command, bool $throw = true): Process
    {
        $environment = [
            'GIT_DIR' => false,
            'GIT_WORK_TREE' => false,
            'GIT_INDEX_FILE' => false,
            'GIT_CONFIG_GLOBAL' => '/dev/null',
            'GIT_CONFIG_SYSTEM' => '/dev/null',
        ];
        $process = new Process($command, $this->workTree, $environment);
        $process->run();

        if ($throw && !$process->isSuccessful()) {
            throw new TemplateSnapshotException(trim($process->getErrorOutput()) ?: 'The Git operation failed.');
        }

        return $process;
    }

    private function locked(callable $callback): mixed
    {
        if (!$this->isAvailable()) {
            throw new TemplateSnapshotException('Git is not available.');
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->workTree);
        $filesystem->mkdir(\dirname($this->gitDir));

        $lock = fopen($this->gitDir.'.lock', 'c');

        if (false === $lock) {
            throw new TemplateSnapshotException('Cannot lock template snapshots.');
        }

        try {
            if (!flock($lock, LOCK_EX)) {
                throw new TemplateSnapshotException('Cannot lock template snapshots.');
            }

            return $callback();
        } finally {
            try {
                $objectsDir = Path::join($this->gitDir, 'objects');

                if ('\\' === \DIRECTORY_SEPARATOR && is_dir($objectsDir)) {
                    // Clear Git's read-only attribute so Windows can delete the cache.
                    $filesystem->chmod($objectsDir, 0o700, recursive: true);
                }
            } finally {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
