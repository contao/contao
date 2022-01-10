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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * @deprecated Deprecated since Contao 4.11, to be removed in Contao 5.0; use
 *             the "contao-setup" binary instead.
 */
class ScriptHandler
{
    /**
     * Runs all Composer tasks to initialize a Contao Managed Edition.
     */
    public static function initializeApplication(Event $event): void
    {
        trigger_deprecation('contao/manager-bundle', '4.11', 'Using ScriptHandler::initializeApplication() has been deprecated and will no longer work in Contao 5.0. Use the "contao-setup" binary instead.');

        $command = ['contao-setup', $event->getIO()->isDecorated() ? '--ansi' : '--no-ansi'];

        $event->getIO()->write(
            sprintf(
                '<warning>Please edit your root composer.json and set "%s" to "@php vendor/bin/%s" instead of using "ScriptHandler::initializeApplication()".</warning>',
                $event->getName(),
                implode(' ', $command)
            )
        );

        if (false === ($phpPath = (new PhpExecutableFinder())->find())) {
            throw new \RuntimeException('The PHP executable could not be found.');
        }

        $command[0] = Path::join(__DIR__.'/../../bin', $command[0]);
        array_unshift($command, $phpPath);

        // Backwards compatibility with symfony/process <3.3 (see #1964)
        if (method_exists(Process::class, 'setCommandline')) {
            $command = implode(' ', array_map('escapeshellarg', $command));
        }

        $process = new Process($command);
        $process->setTimeout(null);

        $process->run(
            static function (string $type, string $buffer) use ($event): void {
                $event->getIO()->write($buffer, false);
            }
        );
    }

    public static function purgeCacheFolder(): void
    {
        trigger_deprecation('contao/manager-bundle', '4.11', 'Using ScriptHandler::purgeCacheFolder() has been deprecated and will no longer work in Contao 5.0.');

        $filesystem = new Filesystem();
        $filesystem->removeDirectory(Path::join(getcwd(), 'var/cache/prod'));
    }

    /**
     * Adds the app directory if it does not exist.
     */
    public static function addAppDirectory(): void
    {
        trigger_deprecation('contao/manager-bundle', '4.11', 'Using ScriptHandler::addAppDirectory() has been deprecated and will no longer work in Contao 5.0.');

        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists(Path::join(getcwd(), 'app'));
    }
}
