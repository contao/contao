<?php

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

/**
 * Sets up the Contao Managed Edition.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScriptHandler
{
    /**
     * Runs all Composer tasks to initialize a Contao Managed Edition.
     *
     * @param Event $event
     */
    public static function initializeApplication(Event $event)
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
    public static function purgeCacheFolder()
    {
        $filesystem = new Filesystem();
        $filesystem->removeDirectory(getcwd().'/var/cache/prod');
    }

    /**
     * Adds the app directory if it does not exist.
     */
    public static function addAppDirectory()
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
    public static function addWebEntryPoints(Event $event)
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
    private static function executeCommand($cmd, Event $event)
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
            function ($type, $buffer) use ($event) {
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
    private static function getVerbosityFlag(Event $event)
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
