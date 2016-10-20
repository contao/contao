<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Sets up the Contao environment in a Symfony app.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ScriptHandler
{
    const RANDOM_SECRET_NAME = 'CONTAO_RANDOM_SECRET';

    /**
     * Adds the Contao directories.
     *
     * @param Event $event
     */
    public static function addDirectories(Event $event)
    {
        self::executeCommand('contao:install', $event);
    }

    /**
     * Generates the symlinks.
     *
     * @param Event $event
     */
    public static function generateSymlinks(Event $event)
    {
        self::executeCommand('contao:symlinks', $event);
    }

    /**
     * Sets the environment variable for the random secret.
     *
     * @param Event $event
     */
    public static function generateRandomSecret(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extra['incenteev-parameters']) || !self::canGenerateSecret($extra['incenteev-parameters'])) {
            return;
        }

        if (!function_exists('random_bytes')) {
            self::loadRandomCompat($event);
        }

        putenv(static::RANDOM_SECRET_NAME.'='.bin2hex(random_bytes(32)));
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
                '%s app/console%s %s%s',
                $phpPath,
                $event->getIO()->isDecorated() ? ' --ansi' : '',
                $cmd,
                static::determineVerbosity($event)
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
     * Determine the verbosity of the console IO of the passed event.
     *
     * @param Event $event
     *
     * @return string
     */
    private static function determineVerbosity(Event $event)
    {
        $io = $event->getIO();

        if ($io->isDebug()) {
            return ' -vvv';
        }
        if ($io->isVeryVerbose()) {
            return ' -vv';
        }
        if ($io->isVerbose()) {
            return ' -v';
        }

        return '';
    }

    /**
     * Checks if there is at least one config file defined but none of the files exists.
     *
     * @param array $config
     *
     * @return bool
     */
    private static function canGenerateSecret(array $config)
    {
        if (isset($config['file'])) {
            return !is_file($config['file']);
        }

        foreach ($config as $v) {
            if (is_array($v) && isset($v['file']) && is_file($v['file'])) {
                return false;
            }
        }

        return !empty($config);
    }

    /**
     * Loads the random_compat library.
     *
     * @param Event $event
     */
    private static function loadRandomCompat(Event $event)
    {
        $composer = $event->getComposer();

        $package = $composer
            ->getRepositoryManager()
            ->getLocalRepository()
            ->findPackage('paragonie/random_compat', '*')
        ;

        if (null === $package) {
            return;
        }

        $autoload = $package->getAutoload();

        if (empty($autoload['files'])) {
            return;
        }

        $path = $composer->getInstallationManager()->getInstaller('library')->getInstallPath($package);

        foreach ($autoload['files'] as $file) {
            include_once $path.'/'.$file;
        }
    }
}
