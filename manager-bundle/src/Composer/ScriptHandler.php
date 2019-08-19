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

use Composer\Script\Event;
use Composer\Util\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ScriptHandler
{
    /**
     * Runs all Composer tasks to initialize a Contao Managed Edition.
     */
    public static function initializeApplication(Event $event): void
    {
        $webDir = self::getWebDir($event);

        $filesystem = new Filesystem();
        $filesystem->removeDirectory(getcwd().'/var/cache/prod');

        static::executeCommand('contao:install-web-dir', $event);
        static::executeCommand('cache:clear --no-warmup', $event);
        static::executeCommand('cache:warmup', $event);
        static::executeCommand(sprintf('assets:install %s --symlink --relative', $webDir), $event);
        static::executeCommand(sprintf('contao:install %s', $webDir), $event);
        static::executeCommand(sprintf('contao:symlinks %s', $webDir), $event);

        $event->getIO()->write('<info>Done! Please open the Contao install tool and make sure the database is up-to-date.</info>');
    }

    /**
     * @throws \RuntimeException
     */
    private static function executeCommand(string $cmd, Event $event): void
    {
        $phpFinder = new PhpExecutableFinder();

        if (false === ($phpPath = $phpFinder->find())) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $process = new Process(
            sprintf(
                '%s %s%s %s%s --env=%s',
                escapeshellarg($phpPath),
                escapeshellarg(__DIR__.'/../../bin/contao-console'),
                $event->getIO()->isDecorated() ? ' --ansi' : '',
                $cmd,
                self::getVerbosityFlag($event),
                getenv('SYMFONY_ENV') ?: 'prod'
            )
        );

        // Increase the timeout according to terminal42/background-process (see #54)
        $process->setTimeout(500);

        $process->run(
            static function (string $type, string $buffer) use ($event): void {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf('An error occurred while executing the "%s" command: %s', $cmd, $process->getErrorOutput())
            );
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
                return ' -vvv';

            case $io->isVeryVerbose():
                return ' -vv';

            case $io->isVerbose():
                return ' -v';

            default:
                return '';
        }
    }
}
