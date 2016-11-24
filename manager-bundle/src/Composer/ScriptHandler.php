<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Composer;

use Composer\Composer;
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
     * Adds the web and console entry points.
     *
     * @param Event $event The event object
     *
     * @throws \RuntimeException
     */
    public static function addEntryPoints(Event $event)
    {
        $composer = $event->getComposer();

        static::installContaoConsole(
            static::findContaoConsole($composer),
            getcwd() . '/bin/console'
        );

        $event->getIO()->write(' Added the console entry point.', false);

        static::executeCommand('contao:install-web-dir --force', $event);
    }

    /**
     * Installs the console and replaces given paths to adjust for installation.
     *
     * @param string $filePath
     * @param string $installTo
     * @param string $vendorDir
     * @param string $kernelRootDir
     */
    private static function installContaoConsole($filePath, $installTo)
    {
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists(dirname($installTo));

        if (!is_file($filePath)) {
            throw new \UnderflowException(sprintf('%s is not a valid file.', $filePath));
        }

        $content = str_replace('../../../../', '../', file_get_contents($filePath));

        if (file_put_contents($installTo, $content) > 0) {
            @chmod($installTo, 0755);
            return;
        }

        throw new \UnderflowException('Contao console script could not be installed.');
    }

    /**
     * Finds the Contao console script in the manager bundle from Composer.
     *
     * @param Composer $composer
     *
     * @return string
     */
    private static function findContaoConsole(Composer $composer)
    {
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getPackages() as $package) {
            if ('contao/manager-bundle' === $package->getName()) {
                return $composer->getInstallationManager()->getInstallPath($package) . '/bin/contao-console';
            }
        }

        throw new \UnderflowException('Contao console script was not found.');
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
                '%s bin/console%s %s%s',
                $phpPath,
                $event->getIO()->isDecorated() ? ' --ansi' : '',
                $cmd,
                self::getVerbosityFlag($event)
            )
        );

        $process->run(
            function ($type, $buffer) use ($event) {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred while executing the "%s" command.', $cmd));
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
            case $io->isVerbose():
                return ' -v';

            case $io->isVeryVerbose():
                return ' -vv';

            case $io->isDebug():
                return ' -vvv';

            default:
                return '';
        }
    }
}
