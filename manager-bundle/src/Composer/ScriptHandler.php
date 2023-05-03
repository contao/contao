<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Composer;

use Composer\IO\IOInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class ScriptHandler
{
    /**
     * Runs all Composer tasks to initialize a Contao Managed Edition.
     */
    public static function initializeApplication(Event $event): void
    {
        $webDir = self::getWebDir($event);

        static::purgeCacheFolder($event->getIO());
        static::executeCommand(['contao:generate-app-secret'], $event);
        static::executeCommand(['contao:install-web-dir'], $event);
        static::executeCommand(['cache:clear', '--no-warmup'], $event);
        static::executeCommand(['cache:clear', '--no-warmup'], $event, 'dev');
        static::executeCommand(['cache:warmup'], $event);
        static::executeCommand(['assets:install', $webDir, '--symlink', '--relative'], $event);
        static::executeCommand(['contao:install', $webDir], $event);
        static::executeCommand(['contao:symlinks', $webDir], $event);

        $event->getIO()->write('<info>Done! Please open the Contao install tool or run contao:migrate on the command line to make sure the database is up-to-date.</info>');
    }

    public static function purgeCacheFolder(IOInterface $io = null): void
    {
        $filesystem = new Filesystem(new ProcessExecutor($io));
        $filesystem->removeDirectory(Path::join(getcwd(), 'var/cache/prod'));
    }

    /**
     * Adds the app directory if it does not exist.
     */
    public static function addAppDirectory(IOInterface $io = null): void
    {
        $filesystem = new Filesystem(new ProcessExecutor($io));
        $filesystem->ensureDirectoryExists(Path::join(getcwd(), 'app'));
    }

    /**
     * @throws \RuntimeException
     */
    private static function executeCommand(array $cmd, Event $event, string $env = 'prod'): void
    {
        $phpFinder = new PhpExecutableFinder();

        if (false === ($phpPath = $phpFinder->find())) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $command = array_merge(
            [$phpPath, __DIR__.'/../../bin/contao-console'],
            $cmd,
            ['--env='.$env, $event->getIO()->isDecorated() ? '--ansi' : '--no-ansi']
        );

        if ($verbose = self::getVerbosityFlag($event)) {
            $command[] = $verbose;
        }

        // Backwards compatibility with symfony/process <3.3 (see #1964)
        if (method_exists(Process::class, 'setCommandline')) {
            $command = implode(' ', array_map('escapeshellarg', $command));
        }

        $process = new Process($command);

        // Increase the timeout according to terminal42/background-process (see #54)
        $process->setTimeout(500);

        $process->run(
            static function (string $type, string $buffer) use ($event): void {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred while executing the "%s" command: %s', implode(' ', $cmd), $process->getErrorOutput()));
        }
    }

    private static function getWebDir(Event $event): string
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        return $extra['symfony-web-dir'] ?? 'web';
    }

    private static function getVerbosityFlag(Event $event): string
    {
        $io = $event->getIO();

        switch (true) {
            case $io->isDebug():
                return '-vvv';

            case $io->isVeryVerbose():
                return '-vv';

            case $io->isVerbose():
                return '-v';

            default:
                return '';
        }
    }
}
