<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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
    /**
     * Adds the Contao directories.
     *
     * @param Event $event The event object
     */
    public static function addDirectories(Event $event)
    {
        self::executeCommand('contao:install', $event);
    }

    /**
     * Generates the symlinks.
     *
     * @param Event $event The event object
     */
    public static function generateSymlinks(Event $event)
    {
        self::executeCommand('contao:symlinks', $event);
    }

    /**
     * Sets environment variable for random secret on installation.
     *
     * @param Event $event The event object
     */
    public static function generateRandomSecret(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        if (!isset($extra['incenteev-parameters']) || !static::canGenerateSecret($extra['incenteev-parameters'])) {
            return;
        }

        putenv('CONTAO_RANDOM_SECRET=' . bin2hex(random_bytes(32)));
    }

    /**
     * Executes a command.
     *
     * @param string $cmd   The command
     * @param Event  $event The event object
     *
     * @throws \RuntimeException If the PHP executable cannot be found or the command cannot be executed
     */
    private static function executeCommand($cmd, Event $event)
    {
        $phpFinder = new PhpExecutableFinder();

        if (false === ($phpPath = $phpFinder->find())) {
            throw new \RuntimeException('The php executable could not be found.');
        }

        $process = new Process(sprintf('%s app/console --ansi %s', $phpPath, $cmd));

        $process->run(
            function ($type, $buffer) use ($event) {
                $event->getIO()->write($buffer, false);
            }
        );

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('An error occurred while executing the "' . $cmd . '" command.');
        }
    }

    /**
     * Validates that we can generate a random secret.
     * We need to make sure at least one file is defined in the config but none of the files exist.
     *
     * @param array $config
     *
     * @return bool
     */
    private static function canGenerateSecret(array $config)
    {
        // "incenteev-parameters" config can be an array of files.
        if (!isset($config['file'])) {
            $result = false;

            foreach ($config as $v) {
                if (is_array($v) && isset($v['file']) && !is_file($v['file'])) {
                    $result = true;
                }
            }

            return $result;
        }

        return !is_file($config['file']);
    }
}
