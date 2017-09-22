<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
     *
     * @param Event $event
     */
    public static function initializeApplication(Event $event): void
    {
        static::purgeCacheFolder();

        static::addAppDirectory();
        static::addWebEntryPoints($event);

        static::executeCommand('cache:clear --no-warmup', $event);
        static::executeCommand('cache:warmup', $event);
        static::executeCommand('assets:install --symlink --relative', $event);

        static::executeCommand('contao:install', $event);
        static::executeCommand('contao:symlinks', $event);
    }

    /**
     * Purges the cache folder.
     */
    public static function purgeCacheFolder(): void
    {
        $filesystem = new Filesystem();
        $filesystem->removeDirectory(getcwd().'/var/cache/prod');
    }

    /**
     * Adds the app directory if it does not exist.
     */
    public static function addAppDirectory(): void
    {
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists(getcwd().'/app');
    }

    /**
     * Adds the web entry points.
     *
     * @param Event $event The event object
     *
     * @throws \RuntimeException
     */
    public static function addWebEntryPoints(Event $event): void
    {
        static::executeCommand('contao:install-web-dir', $event);
    }

    /**
     * Executes a command.
     *
     * @param string $cmd
     * @param Event  $event
     *
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
                '%s %s%s %s%s --env=prod',
                escapeshellarg($phpPath),
                escapeshellarg(__DIR__.'/../../bin/contao-console'),
                $event->getIO()->isDecorated() ? ' --ansi' : '',
                $cmd,
                self::getVerbosityFlag($event)
            )
        );

        $process->run(
            function (string $type, string $buffer) use ($event): void {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf('An error occurred while executing the "%s" command: %s', $cmd, $process->getErrorOutput())
            );
        }
    }

    /**
     * Returns the verbosity flag depending on the console IO verbosity.
     *
     * @param Event $event
     *
     * @return string
     */
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
